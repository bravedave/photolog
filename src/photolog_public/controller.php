<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog_public;

use FilesystemIterator;
use strings;

class controller extends \Controller {
  protected function _fallBack() {
    $this->render([
      'content' => [
        'home/my-darcy',
        'theme',
      ]
    ]);
  }

  protected function _index($k = '') {

    if ($k) {

      $dao = new dao\property_photolog;
      if ($dto = $dao->getByLink($k)) {
        $this->data = (object)[
          'title' => $this->title = $dto->subject,
          'dto' => $dto,
          'key' => $k,
          'files' => []

        ];

        if ($path = $dao->store($dto->id)) {
          // \sys::logger( sprintf('<%s> %s', $path, __METHOD__));

          $files = new FilesystemIterator($path);
          foreach ($files as $file) {
            if (preg_match('@(jp[e]?g|png|mov|mp4|pdf)$@i', $file->getExtension())) {
              $_type = $file->getExtension();
              $type = 'image';
              if (preg_match('@(mov|mp4)$@i', $file->getExtension())) {
                $type = 'video';
              } elseif (preg_match('@(pdf)$@i', $file->getExtension())) {
                $type = 'pdf';
              }

              $this->data->files[] = (object)[
                'description' => $file->getFilename(),
                'url' => strings::url(sprintf('pl/img/%s?img=%s&t=%s', $k, urlencode($file->getFilename()), $file->getMTime())),
                'type' => $type,
                'size' => $file->getSize(),
              ];
            }
          }
        }

        $this->render([
          'content' => [
            'carousel',
            'theme',
          ]
        ]);
      } else {

        $this->_fallBack();
      }
    } else {
      $this->_fallBack();
    }
  }

  protected function before() {
    $this->label = 'Photolog';
    parent::before();

    $this->viewPath[] = __DIR__ . '/views/';
  }

  protected function render($params) {
    $template = '\dvc\pages\bootstrap4';
    $options = array_merge([
      'footer' => false,
      'navbar' => sprintf('%s/app/views/navbar-logo', $this->rootPath),
      'template' => $template

    ], $params);

    $template::$pageContainer = 'container';

    parent::render($options);
  }

  function img($k = '') {
    $fallback = __DIR__ . '/files/default-house.jpg';

    if ($k) {
      if ($img = $this->getParam('img')) {

        if (false == strstr($img, '..')) {
          $dao = new dao\property_photolog;

          if ($dto = $dao->getByLink($k)) {
            if ($path = $dao->store($dto->id)) {

              $file = implode(DIRECTORY_SEPARATOR, [
                $path,
                $img

              ]);

              if (file_exists($file)) {
                \sys::serve($file);
              } else {
                \sys::serve($fallback);
              }
            } else {
              \sys::serve($fallback);
            }
          } else {
            \sys::serve($fallback);
          }
        } else {
          \sys::serve($fallback);
        }
      } else {
        \sys::serve($fallback);
      }
    } else {
      \sys::serve($fallback);
    }
  }

  function index($k = '') {
    $this->isPost() ?
      $this->postHandler() :
      $this->_index($k);
  }
}
