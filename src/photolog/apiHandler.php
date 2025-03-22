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

use bravedave\dvc\{fileUploader, json, logger, ServerRequest};
use cms, Nyholm\Psr7\UploadedFile;
use cms\currentUser;
use SplFileObject;

final class apiHandler {

  static public function getEntriesForProperty(ServerRequest $request): json {

    $action = $request('action');

    if ($pid = (int)$request('id')) {

      $data = (new dao\property_photolog)->getForProperty($pid);
      /*
        {
          "id":"16240",
          "date":"2024-12-06",
          "property_id":"53893",
          "address_street":"3\/200 Baroona Road",
          "subject":"Entry Condition Photos December 2024",
          "updated":"2025-02-15 16:05:51",
          "dirModTime":"2024-12-22 02:13:53",
          "dirStats":"{\"processed\":483,\"queued\":0,\"errors\":0,\"total\":483,\"dirSize\":\"74976\"}",
          "files":{"processed":483,"queued":0,"errors":0,"total":483,"dirSize":"74976"}
          }
          */
      $r = array_map(fn($d) => (object)[
        "id" => $d->id,
        "date" => $d->date,
        "property_id" => $d->property_id,
        "address_street" => $d->address_street,
        "subject" => $d->subject
      ], $data);

      $json = json::ack($action, $r);
      $json->escapeSlashes = false;

      return $json;
    }

    return json::nak($action);
  }

  static public function searchForProperty(ServerRequest $request): json {

    $action = $request('action');

    if ($term = $request('term')) {

      $data = cms\dao\_search::exec('rentalproperty-photolog', $term);
      $data->escapeSlashes = false;
      return $data;
    }

    return (new json);
  }

  static public function upload(ServerRequest $request): json {

    $debug = false;
    $debug = true;
    // $debug = currentUser::isDavid();

    /*--- ---[file types that are accepted]--- ---*/
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
    /*--- ---[file types that are accepted]--- ---*/

    $action = $request('action');
    if ($room_id = (int)$request('room_id')) {

      if ($photolog_id = (int)$request('photolog_id')) {

        if (count($_FILES) == 1) {

          $files = $request->getUploadedFiles();

          /** @var UploadedFile $file */
          $file = array_shift($files);

          // logger::info(sprintf('<%s> %s', get_class($file), logger::caller()));

          // $strType = mime_content_type($file->getClientFilename());
          $strType = $file->getClientMediaType();
          $strFileName = $file->getClientFilename();
          if (preg_match('@\.heic$@', $strFileName)) $strType = 'image/heic';
          // logger::info(sprintf('<%s> %s', $strType, logger::caller()));

          if (in_array($strType, $accept)) {

            $dao = new dao\property_photolog;
            if ($dto = $dao($photolog_id)) {

              $storage = $dao->DiskFileStorage($dto->id, $create = true);
              $Qstorage = $storage->subFolder('queue');

              $Qstorage->touch('.upload-in-progress');

              $target = '';
              $queued = false;
              if ('application/pdf' == $strType || in_array($strType, $videoTypes)) {

                $target = $storage->storeFile($file);
              } else {

                $queued = true;
                $target = $Qstorage->storeFile($file);
              }

              if ($target) {

                $processNow = [
                  'application/pdf',
                  'image/jpeg',
                  'image/pjpeg',
                  'image/png',
                  'image/heic'
                ];

                $fileName = basename($target); // this is the file

                if ($queued) {

                  if (in_array($strType, $processNow)) {

                    $splFile = new SplFileObject($target);

                    // will need to process heic files here
                    if ('heic' == strtolower($splFile->getExtension())) {

                      // logger::info(sprintf('<%s> %s', 'heic file', __METHOD__));
                      try {

                        if ($debug) logger::debug(sprintf('<convert %s> %s', $splFile->getPathname(), logger::caller()));

                        $imagick = new \Imagick;
                        $jpg = \preg_replace('@\.heic$@i', '.jpg', $splFile->getPathname());
                        $imagick->readImage($splFile->getPathname());
                        $imagick->writeImage($jpg);

                        if ($debug) logger::debug(sprintf('<converted %s> %s', $splFile->getPathname(), logger::caller()));
                        unlink($splFile->getPathname());

                        $target = $jpg;
                        $fileName = basename($jpg); // this is the file
                        if ($debug) logger::debug(sprintf('<converted file %s> %s', $fileName, logger::caller()));
                      } catch (\Throwable $th) {

                        logger::info(sprintf('<%s> %s', $splFile->getPathname(), logger::caller()));
                        throw $th;
                      }
                    }

                    // do this now to improve response for autoupdate
                    if ($debug) logger::debug(sprintf('<stamping file %s> %s', $fileName, logger::caller()));
                    utility::stampone(
                      $target,
                      sprintf('%s/%s', $storage->getPath(), $fileName),
                      $dto
                    );
                  }
                }

                $dao = new dao\property_photolog_rooms_tag;
                $dao->tagFileToRoom($photolog_id, $room_id, $fileName);

                // it has been stored, $target is the path
                $json = json::ack($action, ['fileName' => $fileName]);
                $json->escapeSlashes = false;
                return $json;
              } else {

                return json::nak(sprintf('failed to store file - %s', $action));
              }
            }

            return json::nak(sprintf('invalid photolog (%d) - %s', $photolog_id, $action));
          }

          return json::nak(sprintf('invalid file type - %s', $strType))->add('action', $action);
        } elseif (count($_FILES) > 1) {

          return json::nak(sprintf('only one file can be sent - %s', $action));
        }

        return json::nak(sprintf('no files were sent - %s', $action));
      }
    }

    return json::nak($action);
  }
}
