# photolog

Photolog is a way of storing images against a property grouped by date

I use this in a Real Estate Rental situation for storing Routine Inspection Images

## HEIC Format

By default Alpine linux and posssibly other does not install the ImageMagic heic extension, install it with

```bash
sudo apk add imagemagick-heic
```

## Cron Job
This program needs a backup con job, the cron job can be run from the command line
```bash
php cron.php
```
