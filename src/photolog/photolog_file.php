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

class photolog_file {

  public string $description = '';
  public string $file = '';
  public string $extension = '';
  public string $url = '';
  public bool $error = false;
  public int|false $size = false;
  public string $location = '';
  public int $room_id = 0;
  public string $room = '';
  public bool $prestamp = false;

  public function __construct(array $a = []) {

    $this->description = $a['description'] ?? '';
    $this->file = $a['file'] ?? '';
    $this->extension = $a['extension'] ?? '';
    $this->url = $a['url'] ?? '';
    $this->error = $a['error'] ?? false;
    $this->size = $a['size'] ?? false;
    $this->location = $a['location'] ?? '';
    $this->room_id = $a['room_id'] ?? 0;
    $this->room = $a['room'] ?? '';
    $this->prestamp = $a['prestamp'] ?? false;
  }
}
