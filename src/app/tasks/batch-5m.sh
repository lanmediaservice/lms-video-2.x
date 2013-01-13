#!/bin/sh

DIR="$( cd "$( dirname "$0" )" && pwd )"
$DIR/php $DIR/files-tasks.php
$DIR/php $DIR/files-metainfo.php
$DIR/php $DIR/files-frames.php
$DIR/php $DIR/persones-parsing.php
$DIR/php $DIR/files-tth.php
$DIR/php $DIR/trailers-download.php
