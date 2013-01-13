#!/bin/sh

DIR="$( cd "$( dirname "$0" )" && pwd )"
$DIR/php $DIR/indexing.php
$DIR/php $DIR/ranking.php
$DIR/php $DIR/suggestion-cache.php
$DIR/php $DIR/bestsellers.php