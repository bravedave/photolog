<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog;

use bravedave\dvc\{json, logger, ServerRequest, strings};
use cms\entryexit\dao\{
  entryexit_entry_conditions_reports as daoECR,
  dto\entryexit_entry_conditions_reports as dtoECR,
  entryexit_entry_conditions_report_features as daoECRFeatures,
  entryexit_features as daoEntryexitFeatures
};

use cms\{currentUser, routes, openai};
use cms;
use green, smokealarm;
use Intervention\Image\ImageManagerStatic;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use finfo;
use SplFileInfo;
use Nyholm\Psr7\UploadedFile;

final class handler {

  protected static function openai_cache_dir(): string {

    $dir = rtrim(config::cmsStore(), '/') . '/.openai';

    if (!is_dir($dir)) mkdir($dir);
    return $dir;
  }

  protected static function openai_cache_file(int $photolog_id, int $room): string {

    return self::openai_cache_dir() . '/' . $photolog_id . '-' . $room . '.json';
  }

  protected static function updateECR(array $_reply, dtoECR $ecr, int $room_id): void {

    $debug = false;
    $debug = true;

    if ($analysis = ($_reply['analysis'] ?? null)) {

      $daoECR = new cms\entryexit\dao\entryexit_entry_conditions_reports;
      if ($features = $daoECR->getFeaturesForRoom($ecr, $room_id)) {

        if ($debug) logger::dump($analysis, logger::caller());
        array_walk($analysis, function ($v, $key) use ($ecr, $features) {

          if ('report' == strtolower($key)) {
            /**
             * bah - that is not what I asked it to do ...
             * this is a report with a report for each section - doh..
             */

            foreach ($v as $k2 => $v2) {

              $f = array_filter($features, function ($f) use ($k2) {

                if ($f->description == $k2) return true;
                if (strtolower(str_replace('/', '_', $f->description)) == strtolower($k2)) return true;
                return false;
              });

              if ($f) {

                $f = array_shift($f);

                $update = [];
                if ($v2 ?? null) {

                  if (gettype($v2) == 'string') {

                    logger::dump($v2);
                    $update['lessor_comment'] = $v2;

                    $dao = new daoECRFeatures;
                    if ($dto = $dao->getFeatureOfReport($ecr->id, $f->id)) {

                      $dao->UpdateByID($update, $dto->id);
                    } else {

                      $update['entryexit_entry_conditions_reports_id'] = $ecr->id;
                      $update['entryexit_features_id'] = $f->id;
                      $dao->Insert($update);
                    }
                  }
                }
              }
            }
          } else {

            // find the feature with the description that matches the $key
            $f = array_filter($features, function ($f) use ($key) {

              if ($f->description == $key) return true;
              if (strtolower(str_replace('/', '_', $f->description)) == strtolower($key)) return true;
              return false;
            });

            if ($f) {

              $f = array_shift($f);

              $undamaged = 0;
              if ('yes' == ($v->visible ?? 'no')) {

                $undamaged = ('yes' == ($v->damaged ?? 'no')) ? 0 : 1;
              }

              $update = [
                'clean' => ('yes' == ($v->clean ?? 'no')) ? 1 : 0,
                'working' => ('yes' == ($v->working ?? 'no')) ? 1 : 0,
                'undamaged' => $undamaged
              ];

              if ($v->report ?? null) $update['lessor_comment'] = $v->report;

              $dao = new daoECRFeatures;
              if ($dto = $dao->getFeatureOfReport($ecr->id, $f->id)) {

                $dao->UpdateByID($update, $dto->id);
              } else {

                $update['entryexit_entry_conditions_reports_id'] = $ecr->id;
                $update['entryexit_features_id'] = $f->id;
                $dao->Insert($update);
              }
            }
          }
        });
      }
    }
  }

  public static function analyseDamage(ServerRequest $request): json {

    $debug = false;
    $debug = true;
    $action = $request('action');

    if ($id = (int)$request('id')) {

      if ($room = (int)$request('room')) {

        $dao = new dao\property_photolog;
        if ($dto = $dao->getByID($id)) {

          /** @var dao\dto\property_photolog $dto */

          $storage = $dao->DiskFileStorage($dto->id, $create = false);
          if ($storage->isValid()) {

            $_images = (array)$request('images');
            // logger::dump($_images);

            /*
            curl https://api.openai.com/v1/chat/completions \
              -H "Content-Type: application/json" \
              -H "Authorization: Bearer YOUR_OPENAI_API_KEY" \
              -d '{
                "model": "gpt-4-vision-preview",
                "messages": [
                  {
                    "role": "system",
                    "content": "You are an assistant that analyzes images for property condition reports. Provide a structured description of the walls in the images."
                  },
                  {
                    "role": "user",
                    "content": [
                      {"type": "text", "text": "Analyze the following images and describe the condition of the walls, including color, cleanliness, marks, and any hooks or nails."},
                      {"type": "image_url", "image_url": "data:image/jpeg;base64,YOUR_BASE64_IMAGE_1"},
                      {"type": "image_url", "image_url": "data:image/jpeg;base64,YOUR_BASE64_IMAGE_2"},
                      {"type": "image_url", "image_url": "data:image/jpeg;base64,YOUR_BASE64_IMAGE_3"},
                      {"type": "image_url", "image_url": "data:image/jpeg;base64,YOUR_BASE64_IMAGE_4"},
                      {"type": "image_url", "image_url": "data:image/jpeg;base64,YOUR_BASE64_IMAGE_5"}
                    ]
                  }
                ],
                "max_tokens": 500
              }'  */

            // we need base64 encoded images
            $images = [];
            $accepted = [
              'image/jpeg',
              'image/png'
            ];

            foreach ($_images as $_img) {

              if ($data = $storage->getFile($_img)) {

                $mime  = $storage->mime_type($_img);
                if (in_array($mime, $accepted)) {


                  $img = ImageManagerStatic::make($data);  // open an image file
                  $h = $img->height();
                  $w = $img->width();

                  // $stampSize = 1024;
                  $stampSize = 600;
                  $stampSize = 500;
                  // $stampSize = 400;

                  $quality = 90;
                  $quality = 80;

                  /**
                   * now you are able to resize the instance
                   * if it's a landscape picture, resize with height constraint
                   */
                  if ($w > $h) {

                    $img->resize(null, $stampSize, function ($constraint) {
                      $constraint->aspectRatio();
                    });
                  } else {

                    $img->resize($stampSize, null, function ($constraint) {
                      $constraint->aspectRatio();
                    });
                  }

                  $basename = basename($_img);
                  $fileName = config::tempdir() . $basename;
                  $img->save($fileName, $quality);

                  $data = file_get_contents($fileName);
                  unlink($fileName);

                  $images[$basename] = 'data:' . $mime . ';base64,' . base64_encode($data);
                  // $images[] = $img->encode('data-url');
                }
              }
            }

            if (count($images) > 0) {

              $query = "Analyze the following images and describe the condition of the walls and surfaces, including color, cleanliness, marks, any hooks or nails, switches, power outlets, and any other visible damage.";
              // $query = "Analyze the following images and return a structured JSON object using these specific labels: 'wall_condition', 'floor_status', and 'ceiling_analysis'. Include details such as color, cleanliness, marks, hooks or nails, switches, power outlets, and any visible damage.";

              $ecr = null;
              $features = null;
              $sections = ['wall_condition', 'floor_status', 'ceiling_analysis'];
              if ($dto->entryexit_entry_conditions_reports_id) {

                $daoECR = new cms\entryexit\dao\entryexit_entry_conditions_reports;
                if ($ecr = $daoECR($dto->entryexit_entry_conditions_reports_id)) {

                  if ($ecr->features) {

                    if ($features = $daoECR->getFeaturesForRoom($ecr, $room)) {

                      $sections = array_map(fn($f) => $f->description, $features);
                    }

                    // logger::dump($sections, logger::caller());
                  }
                }
              }

              $sectionalDetail = array_map(function ($section) {

                $a = explode('/', $section);
                return sprintf('For the section %s please provide detail for %s.', $section, implode(' and ', $a));
              }, array_filter($sections, fn($section) => str_contains($section, '/')));

              if (count($sections) > 1) {

                $_last = array_pop($sections);
                $sections = implode(',', $sections) . ' and ' . $_last;
              } else {

                $sections = implode(',', $sections);
              }

              $query = 'Analyze the following images and return a structured JSON object using these specific labels: ' .
                $sections .
                " Provide ratings for each section using labels for clean, working, visble and damaged using values yes or leave blank." .
                " for each section using the label report, provide a verbal report for each section." .
                " if the aspect is not visible in the verbal report write 'not visible'.";

              $query = 'Analyze the following images and return a structured JSON object using these specific labels: ' .
                $sections .
                " Apply ratings (clean, working, visible, damaged) using yes or leave blank," .
                " and include a report field with a verbal condition summary or 'not visible' if unclear." .
                " Do not wrap the output in an additional parent key.";

              // $query = 'Analyze the following images and return a structured JSON object using these specific labels: ' .
              //   $sections .
              //   " Apply ratings (clean, working, visible, damaged with yes or blank) and a report field with a separate verbal condition summary per section," .
              //   " and include a report field with a verbal condition summary including wall color" .
              //   " and whether floors are carpeted or tiled (if visible)," .
              //   " or 'not visible' if unclear." .
              //   " Do not wrap the output in an additional parent key.";

              $_query = [
                "under a key 'analysis',",
                'Analyze the following images and return a structured JSON object ',
                'where each predefined label (' . $sections . ') is a nested key.',

                'Each section must include a rating fields for',
                'clean (yes or blank),',
                'working (yes or blank),',
                'visible (yes or blank),',
                'damaged (yes or blank),',
                'and a report field with a separate verbal condition summary per section.',

                'The report field should include wall/ceiling color,',
                'whether floors are carpeted or tiled,',
                'look for cracks in tiles, marks, scratches and waterstain,',
                'look for evidence lights and appliances are working,',
                'and the general condition of the section.',
                'Provide descriptions for individual aspects (e.g., "Doors and walls and ceiling" for "Doors/walls/ceiling").',

                'Record any brand names, model numbers, and serial numbers from visible labels in the report.',

                "If a feature is not visible, explicitly state 'not visible.'",

                // implode(' ', $sectionalDetail),

                "Use definitive language - state findings as they are, without uncertainty.",

                "Under a key 'serial numbers',",
                "Extract and return all text from images containing visible labels.",
                "Try to gather brand names, serial number, makes and models of appliances.",
                "If text is unreadable or unclear, state 'Label present but not readable'.",
                "If possible, use the corresponding appliance type as the key for the output."
              ];
              // "Do not summarize or interpret—return the exact text from labels as-is.",
              // 'Do not wrap the output in an additional parent key.'

              // 'When labels with brand or model information are present, extract and use that data accurately in the report.',
              $query = implode(' ', $_query);

              $content = [
                (object)[
                  "type" => "text",
                  "text" => $query
                ],
              ];

              foreach ($images as $key => $img) {

                // $content[] = (object)[
                //   "type" => "text",
                //   "text" => sprintf("Image %s", $key)
                // ];
                $content[] = (object)[
                  "type" => "image_url",
                  "image_url" => (object)[
                    "url" => $img
                  ],
                ];
              }

              // $role = "You are an assistant that analyzes images for property condition reports. Provide a structured description of the walls, floors, ceiling and fittings in the images.";
              $role = "You are an assistant that analyzes images for property condition reports. Provide a structured JSON response using the specified labels.";
              // 2025-02-20 - role updated
              $role = implode(' ', [
                "You are an assistant that analyzes images for property condition reports.",
                "Your primary tasks include evaluating property features based on predefined labels",
                "and extracting text from visible labels, including serial numbers.",
                "Provide a structured JSON response that includes condition assessments, cleanliness, functionality,",
                "visibility, and any detected text (e.g., model and serial numbers)."
              ]);
              $messages = [
                (object)[
                  "role" => "system",
                  "content" => $role
                ],
                (object)[
                  "role" => "user",
                  "content" => $content
                ]
              ];

              if ($debug) {

                // $tmpName = config::cmsStore() . 'openai-payload.json';
                // if (file_exists($tmpName)) unlink($tmpName);
                // file_put_contents($tmpName, json_encode([
                //   "model" => "gpt-4o-mini",
                //   "messages" => $messages,
                //   "max_tokens" => 1000,
                //   "response_format" => "json"
                // ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                // logger::debug(sprintf('<wrote to %s> %s', $tmpName, logger::caller()));
                // // return json::nak($action);
              }

              if (1 == (int)$request('fake')) {

                logger::info(sprintf('<%s> %s', 'fake request', logger::caller()));
                return json::nak($action);
              }

              $model = new openai\models\chat;
              $model->messages = $messages;
              $model->max_tokens = 2000;
              $model->response_format = 'json';
              if ($debug) $model->debug = true;

              // $tmpName = config::cmsStore() . 'openai-reply.json';
              // if ($response = json_decode(file_get_contents($tmpName))) {
              if ($response = (new openai\chat)($model)) {

                $cacheFile = self::openai_cache_file($dto->id, $room);
                if (file_exists($cacheFile)) unlink($cacheFile);
                file_put_contents($cacheFile, json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                if ($debug) logger::debug(sprintf('<cached: %s> %s', $cacheFile, logger::caller()));

                $reply = '';
                if (is_array($response)) {

                  // get the first element off the array
                  $msg = $response[0] ?? null;
                  if (!!$msg) {

                    $msg = (object)$msg;
                    if ($msg->message ?? null) {

                      if ($_reply = ($msg->message->content ?? null)) {

                        if ('json' == $model->response_format) {

                          $_json = json_decode($_reply);
                          if (!!$_json) {

                            if ($debug) logger::debug(sprintf('<processing json> %s', logger::caller()));
                            if ($ecr) self::updateECR((array)$_json, $ecr, $room);
                          }

                          $_text = sprintf(
                            "```json\n%s\n```",
                            json_encode(
                              $_json,
                              JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                            )
                          );

                          $converter = new GithubFlavoredMarkdownConverter;
                          // logger::debug(sprintf('<%s> %s', $markdown, logger::caller()));

                          $reply =  (string)$converter->convert($_text);
                        } else {

                          $mdo = [
                            'allow_unsafe_links' => $options['allow_unsafe_links'] ?? false,
                            'html_input' => $options['html_input'] ?? 'strip'
                          ];

                          $converter = new GithubFlavoredMarkdownConverter($mdo);
                          // logger::debug(sprintf('<%s> %s', $markdown, logger::caller()));

                          $reply =  (string)$converter->convert($_reply);
                        }
                      }
                    }
                  }
                }

                return json::ack($action, $response)
                  ->add('reply', $reply);
              } else {

                logger::dump($response, logger::caller());
              }
            }
          }
        }
      }

      return json::nak(sprintf('missing room - %s', $action));
    }

    return json::nak($action);
  }

  public static function analyseDamageReprocess(ServerRequest $request): json {

    $debug = false;
    // $debug = true;
    $action = $request('action');

    $id = (int)$request('id');
    $room = (int)$request('room');

    if (!$id) return json::nak(sprintf('missing id - %s', $action));
    if (!$room) return json::nak(sprintf('missing room - %s', $action));

    $dao = new dao\property_photolog;
    if ($dto = $dao->getByID($id)) {

      /** @var dao\dto\property_photolog $dto */

      if ($dto->entryexit_entry_conditions_reports_id < 1) return json::nak(sprintf('not ecr attached - %s', $action));

      $daoECR = new cms\entryexit\dao\entryexit_entry_conditions_reports;
      if ($ecr = $daoECR($dto->entryexit_entry_conditions_reports_id)) {

        $cacheFile = self::openai_cache_file($dto->id, $room);
        if (file_exists($cacheFile)) {

          if ($response = json_decode(file_get_contents($cacheFile))) {

            $reply = '';
            if (is_array($response)) {

              // get the first element off the array
              $msg = $response[0] ?? null;
              if (!!$msg) {

                $msg = (object)$msg;
                if ($msg->message ?? null) {

                  if ($_reply = ($msg->message->content ?? null)) {

                    if ($_json = json_decode($_reply)) {

                      if ($debug) logger::debug(sprintf('<processing json> %s', logger::caller()));
                      self::updateECR((array)$_json, $ecr, $room);

                      $_text = sprintf(
                        "```json\n%s\n```",
                        json_encode($_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                      );

                      $converter = new GithubFlavoredMarkdownConverter;
                      // logger::debug(sprintf('<%s> %s', $markdown, logger::caller()));

                      $reply =  (string)$converter->convert($_text);
                    } else {

                      $mdo = [
                        'allow_unsafe_links' => $options['allow_unsafe_links'] ?? false,
                        'html_input' => $options['html_input'] ?? 'strip'
                      ];

                      $converter = new GithubFlavoredMarkdownConverter($mdo);
                      // logger::debug(sprintf('<%s> %s', $markdown, logger::caller()));

                      $reply =  (string)$converter->convert($_reply);
                    }
                  }
                }
              }
            }

            return json::ack($action, $response)
              ->add('reply', $reply);
          };
        }

        return json::nak(sprintf('cacheFile not found - %s', $action));
      }

      return json::nak(sprintf('entry CR not found - %s', $action));
    }

    return json::nak(sprintf('not found - %s', $action));
    return json::nak($action);
  }

  public static function cron(ServerRequest $request): json {

    utility::stamp();
    return json::ack('cron run complete');
  }

  public static function delete(ServerRequest $request): json {

    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      $storage = $dao->DiskFileStorage($id, $create = false);
      if ($storage->isValid()) {

        $_file = trim($request('file'), './ ');
        if ($_file) {

          $storage->deleteFile($_file);
          $storage->deleteFile($_file . config::photolog_prestamp);

          $Qstorage = $storage->subFolder('queue', $create = false);
          if ($Qstorage->isValid()) {

            if ($Qstorage->file_exists($_file)) {

              $errfile = sprintf(
                '%s.err',
                basename($Qstorage->getPath($_file))
              );
              $Qstorage->deleteFile($_file);
              $Qstorage->deleteFile($errfile);
            }
          }
        }
      }

      return json::ack($request('action'));
    }

    return json::nak($request('action'));
  }

  public static function deleteEntry(ServerRequest $request): json {

    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao($id)) {

        if (0 == $dto->files->total) {

          $storage = $dao->DiskFileStorage($dto->id, $create = false);
          if ($storage->isValid()) {

            $Qstorage = $storage->subFolder('queue');
            if ($Qstorage->isValid()) $Qstorage->delete();

            if ($storage->file_exists('_info.json')) $storage->deleteFile('_info.json');
            $storage->delete();
          }

          $dao->delete($id);
          return json::ack($request('action'));
        } else {

          return json::nak(sprintf('%s %d files', $request('action'), $dto->files->total));
        }
      }
    }

    return json::nak($request('action'));
  }

  public static function entryConditionReportSet(ServerRequest $request): json {

    if ($pid = (int)$request('id')) {

      $dao = new dao\property_photolog;
      $dao->UpdateByID(['entry_condition_report' => $request('value')], $pid);
      return json::ack($request('action'));
    }

    return json::nak($request('action'));
  }

  public static function getPhotolog(ServerRequest $request): json {

    if ($pid = (int)$request('property')) {

      $dao = new dao\property_photolog;
      return json::ack($request('action'))->data($dao->getForProperty($pid));
    }

    return json::nak($request('action'));
  }

  public static function getPhotologFile(ServerRequest $request): json {

    $debug = false;
    // $debug = true;

    $action = $request('action');

    if ($id = (int)$request('id')) {

      if ($_in_file = $request('file')) {

        $dao = new dao\property_photolog;
        if ($dto = $dao($id)) {

          $storage = $dao->DiskFileStorage($id, $create = false);
          if ($storage->isValid()) {

            if ($storage->file_exists($_in_file)) {

              if ($finfo = $storage->getFileInfo($_in_file)) {

                $file = new photolog_file;
                $file->file = $file->description = $fileName = $finfo->getFilename();
                $file->extension = $finfo->getExtension();
                $file->url = strings::url(sprintf(
                  '%s/img/%d?img=%s&t=%s',
                  routes::photolog,
                  $dto->id,
                  urlencode($fileName),
                  $finfo->getMTime()
                ));
                $file->size = $finfo->getSize();
                $file->prestamp = file_exists($finfo->getRealPath() . config::photolog_prestamp);

                if ($imgInfo = $dao->getImageInfo($dto, $fileName)) {

                  if (isset($imfInfo->location)) $file->location =  (string)$imgInfo->location;
                }

                $roomTags = $dto->property_photolog_rooms_tags ?? null;
                if (!is_null($roomTags)) {

                  $tag = array_search($fileName, array_map(fn($tag) => $tag->file, $roomTags));
                  if ($tag !== false) {

                    if ($roomTags[$tag] ?? null) {

                      $file->room_id = $roomTags[$tag]->property_rooms_id;
                      $file->room = $roomTags[$tag]->name ?? '';
                    }
                  }
                }

                return json::ack($action, $file);
              }
            }
          }
        }

        return json::nak(sprintf('not found - %s', $action));
      }

      if ($debug) logger::debug(sprintf('<invalid id/file - %s> %s', $action, logger::caller()));
    }

    if ($debug) logger::debug(sprintf('<%s> %s', $action, logger::caller()));
    return json::nak($action);
  }

  public static function openaiCacheFileDelete(ServerRequest $request): json {

    $action = $request('action');
    // logger::info(sprintf('<%s> %s', $action, logger::caller()));

    if ($id = (int)$request('id')) {

      if ($room = (int)$request('room')) {

        $cacheFile = self::openai_cache_file($id, $room);
        // logger::info(sprintf('<%s> %s', $cacheFile, logger::caller()));

        if (file_exists($cacheFile)) unlink($cacheFile);
        return json::ack($action);
      }
    }

    return json::nak($action);
  }

  public static function openaiCacheFileExists(ServerRequest $request): json {

    $action = $request('action');
    // logger::info(sprintf('<%s> %s', $action, logger::caller()));

    if ($id = (int)$request('id')) {

      if ($room = (int)$request('room')) {

        $cacheFile = self::openai_cache_file($id, $room);
        // logger::info(sprintf('<%s> %s', $cacheFile, logger::caller()));

        if (file_exists($cacheFile)) return json::ack($action);
      }
    }

    return json::nak($action);
  }

  public static function propertySmokeAlarms(ServerRequest $request): json {

    $action = $request('action');
    if ($id = (int)$request('id')) {

      $alarms = [];
      if (class_exists('smokealarm\dao\smokealarm')) {

        $dao = new dao\property_photolog;
        if ($dto = $dao->getByID($id)) {

          $dao = new smokealarm\dao\smokealarm;
          if ($res = $dao->getForProperty($dto->property_id)) {

            $alarms = (array)$res->dtoSet();
          }
        }
      }

      return json::ack($action)
        ->add('alarms', $alarms);
    }

    return json::nak($action);
  }

  public static function publicLinkCreate(ServerRequest $request): json {

    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $a = ['public_link_expires' => ''];
        if (strtotime($request('public_link_expires')) > time()) {

          $a = [
            'public_link' => bin2hex(random_bytes(11)),
            'public_link_expires' => $request('public_link_expires')
          ];
        }

        $dao->UpdateByID($a, $dto->id);
        return json::ack($request('action'));
      }
    }

    return json::nak($request('action'));
  }

  public static function publicLinkGet(ServerRequest $request): json {

    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        if (strtotime($dto->public_link_expires) > time()) {

          return json::ack($request('action'))
            ->add('url', sprintf('%spl/%s', config::$PORTAL, $dto->public_link))
            ->add('expires', $dto->public_link_expires);
        }
      }
    }

    return json::nak($request('action'));
  }

  public static function publicLinkClear(ServerRequest $request): json {

    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $a = [
          'public_link' => '',
          'public_link_expires' => ''
        ];

        $dao->UpdateByID($a, $dto->id);
        return json::ack($request('action'));
      }
    }

    return json::nak($request('action'));
  }

  public static function renameFile(ServerRequest $request): json {

    if ($id = (int)$request('id')) {

      $oldfile = trim($request('file'), './ ');
      if ($oldfile) {

        $dao = new dao\property_photolog;
        if ($dto = $dao->getByID($id)) {

          $storage = $dao->DiskFileStorage($dto->id, $create = false);
          if ($storage->isValid()) {

            if ($storage->file_exists($oldfile)) {

              $newfile = strings::safe_file_name(trim($request('newfile'), './ '));
              if (!$newfile) {

                return json::nak(sprintf('%s : invalid new name', $request('action')));
              }

              // the new file must preserve the extension
              $ext = '.' . pathinfo($storage->getPath($oldfile), PATHINFO_EXTENSION);
              if (substr($newfile, -strlen($ext)) != $ext) {

                $newfile .= $ext;
              }

              if ($storage->file_exists($newfile)) {

                return json::nak(sprintf('%s : %s already exists', $request('action'), $newfile));
              }

              $storage->rename($oldfile, $newfile);
              $dao->renameImageInfo($dto, $oldfile, $newfile);
              return json::ack($request('action'));
            }
          }
        }
      }
    }

    return json::nak($request('action'));
  }

  public static function rotate(ServerRequest $request): json {

    $action = $request('action');

    if ($id = (int)$request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $_file = trim($request('file'), './ ');
        $storage = $dao->DiskFileStorage($dto->id, $create = false);
        if ($storage->isValid()) {

          if ($storage->file_exists($_file)) {

            $file = $storage->getPath($_file);
            if (file_exists($file . config::photolog_prestamp)) {

              if (utility::rotate(
                $file,
                'rotate-180' == $action ?
                  config::photolog_rotate_180 : ('rotate-left' == $action ?
                    config::photolog_rotate_left : config::photolog_rotate_right)
              )) {

                if (file_exists($file)) {

                  $info = new SplFileInfo($file);
                  $imgInfo = $dao->getImageInfo($dto, $info->getFilename());

                  $returnfile = (object)[
                    'description' => $info->getFilename(),
                    'extension' => $info->getExtension(),
                    'url' => strings::url(sprintf(
                      '%s/img/%d?img=%s&t=%s',
                      routes::photolog,
                      $dto->id,
                      urlencode($info->getFilename()),
                      $info->getMTime()
                    )),
                    'error' => false,
                    'size' => $info->getSize(),
                    'location' => $imgInfo->location ?? '',
                    'prestamp' => file_exists($info->getRealPath() . config::photolog_prestamp)
                  ];

                  return json::ack($action)->data($returnfile);
                }
              }

              return json::nak($action);
            }

            return json::nak(sprintf('missing pre-stamp : %s', $action));
          }
        }
      }

      return json::nak(sprintf('not found - %s', $action));
    }

    return json::nak($action);
  }

  public static function save(ServerRequest $request): json {

    $action = $request('action');

    if ($property_id = $request('property_id')) {

      $a = [
        'property_id' => $property_id,
        'subject' => $request('subject'),
        'date' => $request('date')
      ];

      $dao = new dao\property_photolog;

      if ('update-entry' == $action) {

        if ($id = (int)$request('id')) {

          $dao->UpdateByID($a, $id);
          return json::ack($action)
            ->add('id', $id);
        }
      } else {

        $id = $dao->Insert($a);
        return json::ack($action)
          ->add('id', $id);
      }
    }

    return json::nak($action);
  }

  public static function saveNotepad(ServerRequest $request): json {

    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $a = [
          'notes' => $request('notes')
        ];

        $dao->UpdateByID($a, $dto->id);
        return json::ack($request('action'))->data($a);
      }
    }
    return json::nak($request('action'));
  }

  public static function searchProperties(ServerRequest $request): json {


    if ($term = $request('term')) {

      return json::ack($request('action'))
        ->add('term', $term)
        ->data(green\search::properties($term));
    }

    return json::nak($request('action'));
  }

  public static function setAlarmLocation(ServerRequest $request): json {

    if ($id = (int)$request('id')) {

      if ($file = (string)$request('file')) {

        if ($location = (string)$request('location')) {

          $dao = new dao\property_photolog;
          if ($dto = $dao->getByID($id)) {

            $info = $dao->getImageInfo($dto, $file);
            $info->location = $location;
            $dao->setImageInfo($dto, $file, $info);

            return json::ack($request('action'));
          }
        }
      }
    }

    return json::nak($request('action'));
  }

  public static function setAlarmLocationClear(ServerRequest $request): json {

    $action = $request('action');
    if ($id = $request('id')) {

      if ($file = $request('file')) {

        $dao = new dao\property_photolog;
        if ($dto = $dao->getByID($id)) {

          $info = $dao->getImageInfo($dto, $file);
          if (isset($info->location)) {

            unset($info->location);
            $dao->setImageInfo($dto, $file, $info);
          }

          return json::ack($action);
        }
      }
    }

    return json::nak($action);
  }

  public static function setAssociatedEntryConditionReport(ServerRequest $request): json {
    $action = $request('action');

    $action = $request('action');
    if ($id = (int)$request('id')) {


      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $a = [
          'entryexit_entry_conditions_reports_id' => $request('entryexit_entry_conditions_reports_id')
        ];

        $dao->UpdateByID($a, $id);
        return json::ack($action);
      }
    }

    return json::nak($action);
  }

  public static function tagClear(ServerRequest $request): json {

    $action = $request('action');
    if ($id = (int)$request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $storage = $dao->DiskFileStorage($id, $create = false);
        if ($storage->isValid()) {

          $file = trim($request('file'), './ ');
          if ($file) {

            if ($storage->file_exists($file)) {

              $dao = new dao\property_photolog_rooms_tag;
              $dao->tagFileClear($id, $file);
              return json::ack($action);
            }
          }
        }
      }
    }

    return json::nak($action);
  }

  public static function tagToRoom(ServerRequest $request): json {

    $action = $request('action');
    if ($id = (int)$request('id')) {

      if ($room_id = (int)$request('room_id')) {

        $dao = new dao\property_photolog;
        if ($dto = $dao->getByID($id)) {

          $storage = $dao->DiskFileStorage($id, $create = false);
          if ($storage->isValid()) {

            $file = trim($request('file'), './ ');
            if ($file) {

              if ($storage->file_exists($file)) {

                $dao = new dao\property_photolog_rooms_tag;
                $dao->tagFileToRoom($id, $room_id, $file);
                return json::ack($action);
              }
            }
          }
        }
      }
    }

    return json::nak($action);
  }

  public static function touch(ServerRequest $request): json {

    $action = $request('action');
    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $storage = $dao->DiskFileStorage($dto->id, $create = false);
        if ($storage->isValid()) {

          $touch = $storage->getPath() . '/temp.dat';
          touch($touch);
          unlink($touch);

          return json::ack($action);
        }
      }
    }

    return json::nak($action);
  }

  public static function upload(ServerRequest $request): json {

    $debug = false;
    // $debug = currentUser::isDavid();

    $action = $request('action');

    $id = (int)$request('id');
    $location = '';

    if ($tag = $request('tag')) {

      $id = 0;

      if (class_exists('smokealarm\dao\smokealarm')) {

        if ('smokealarm' == $tag) {

          if ($smokealarm_id = (int)$request('smokealarm_id')) {

            $dao = new smokealarm\dao\smokealarm;
            if ($dto = $dao->getByID($smokealarm_id)) {

              if ($dto->properties_id) {

                $dao = new dao\property_photolog;
                if ($logs = $dao->getForProperty($dto->properties_id)) {

                  foreach ($logs as $log) {

                    if (preg_match('@^smoke alarm audit@i', $log->subject)) {

                      if (($t = strtotime($log->date)) > 0) {

                        if (date('Y-m') == date('Y-m', $t)) {

                          // use this one ..
                          $id = $log->id;
                          break;
                        }
                      }
                    }
                  }
                }
              }

              if (!$id) {

                $dao = new dao\property_photolog;
                $id = $dao->Insert([
                  'property_id' => $dto->properties_id,
                  'subject' => sprintf('Smoke Alarm Audit %s', date('M Y')),
                  'date' => date('Y-m-d')
                ]);
              }

              $location = $request('location');
            }
          }
        }
      }
    }

    $videoTypes = ['video/quicktime', 'video/mp4'];

    $accept = [
      'application/pdf',
      'image/jpeg',
      'image/pjpeg',
      'image/png'
    ];

    if (config::$PHOTOLOG_ENABLE_VIDEO) {
      $accept[] = 'video/quicktime';
      $accept[] = 'video/mp4';
    }

    /** heic are not current supported on fedora */
    if (config::$PHOTOLOG_ENABLE_HEIC) {
      $accept[] = 'image/heic';
      $accept[] = 'image/heif';
    }

    if ($id) {

      $dao = new dao\property_photolog;
      if ($dto = $dao($id)) {

        $storage = $dao->DiskFileStorage($dto->id, $create = true);
        $Qstorage = $storage->subFolder('queue');

        $iUploaded = 0;
        $badFiles = [];
        $goodFiles = [];
        foreach ($request->getUploadedFiles() as $file) {

          /** @var UploadedFile $file */
          if ($debug) logger::debug(sprintf('<%s> %s', get_class($file), logger::caller()));

          $mimeType = $file->getClientMediaType();
          if ('application/octet-stream' == $mimeType) {

            /**
             * If getClientMediaType() returns application/octet-stream,
             * it means the MIME type detection is unreliable or the client
             * did not properly send the Content-Type header for the file.
             *
             * This often happens with HEIC files because not all systems
             * correctly assign their media type.
             *
             *
             */
            if ('application/octet-stream' == $mimeType) {

              $finfo = new finfo(FILEINFO_MIME_TYPE);
              $mimeType = $finfo->file($file->getStream()->getMetadata('uri'));
              if ($debug) logger::debug(sprintf('<retrieved mimetype using finfo %s> : %s', $mimeType, __METHOD__));
            }
          }

          if (in_array($mimeType, $accept)) {

            if ($debug) logger::debug(sprintf('<%s (%s) acceptable> : %s', $file->getClientFilename(), $mimeType, __METHOD__));

            $target = '';
            if ('application/pdf' == $mimeType || in_array($mimeType, $videoTypes)) {

              $target = $storage->storeFile($file);
            } else {

              $target = $Qstorage->storeFile($file);
            }

            if ($target) {

              $iUploaded++;
              $fileInfo = new SplFileInfo($target);
              $fileName = $fileInfo->getFilename();

              chmod($target, 0666);
              // logger::info(sprintf('<%s> %s', $target, __METHOD__));

              if ($debug) logger::debug(sprintf('upload: %s (%s) accepted : %s', $fileName, $mimeType, __METHOD__));
              $goodFiles[] = [
                'description' => $fileName,
                'url' => strings::url(sprintf(routes::photolog . '/img/%d?img=%s&t=%s', $dto->id, $fileName, $fileInfo->getMTime()))
              ];

              if ($location) {

                if (!('application/pdf' == $mimeType || in_array($mimeType, $videoTypes))) {

                  // do this now to improve response for autoupdate
                  utility::stampone(
                    $target,
                    sprintf('%s/%s', $storage->getPath(), $fileName),
                    $dto
                  );
                }

                $info = $dao->getImageInfo($dto, $fileName);
                $info->location = $location;
                $dao->setImageInfo($dto, $fileName, $info);
              }
            } else {

              logger::info(sprintf('<lost file after upload .. %s> %s', $target, __METHOD__));
              $badFiles[] = $file->getClientFilename();
            }
          } else {

            // error ?
            $badFiles[] = $file->getClientFilename();
          }
        }

        if ($iUploaded > 0) {

          if (count($badFiles) > 0) {

            return json::nak($action, $badFiles)->add('files', $goodFiles);
          } else {

            return json::ack($action)->add('files', $goodFiles);
          }
        }
      }
    }

    return json::nak($action);
  }
}
