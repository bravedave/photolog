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

use bravedave\dvc\{json, ServerRequest, strings};
use cms\{routes};
use SplFileInfo;

final class handler {

  public static function getPhotolog(ServerRequest $request): json {

    if ($pid = (int)$request('property')) {

      $dao = new dao\property_photolog;
      return json::ack($request('action'))->data($dao->getForProperty($pid));
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

    if ($id = $request('id')) {

      if ($file = $request('file')) {

        $dao = new dao\property_photolog;
        if ($dto = $dao->getByID($id)) {

          $info = $dao->getImageInfo($dto, $file);
          if (isset($info->location)) {

            unset($info->location);
            $dao->setImageInfo($dto, $file, $info);
          }

          return json::ack($request('action'));
        }
      }
    }

    return json::nak($request('action'));
  }

  public static function touch(ServerRequest $request): json {

    if ($id = $request('id')) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByID($id)) {

        $storage = $dao->DiskFileStorage($dto->id, $create = false);
        if ($storage->isValid()) {

          $touch = $storage->getPath() . '/temp.dat';
          touch($touch);
          unlink($touch);

          return json::ack($request('action'));
        }
      }
    }

    return json::nak($request('action'));
  }
}
