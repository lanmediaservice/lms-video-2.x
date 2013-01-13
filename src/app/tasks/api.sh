#!/bin/sh

DIR="$( cd "$( dirname "$0" )" && pwd )"
$DIR/php $DIR/api.php $@
