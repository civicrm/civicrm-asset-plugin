#!/usr/bin/env bash

function absdirname() {
  pushd $(dirname $0) >> /dev/null
    pwd
  popd >> /dev/null
}

SCRIPT_DIR=$(absdirname "$0")
PROJECT_DIR=$(dirname "$SCRIPT_DIR")
WORK_DIR="$PROJECT_DIR/build"
#COMPOSER_VERS=('2.0.14')
COMPOSER_VERS=('1.10.26' '2.0.14' '2.1.14' '2.2.21' '2.5.4')
PHPUNIT_EXTRA=('--debug')

mkdir -p "$WORK_DIR" "$WORK_DIR/junit" "$WORK_DIR/tools" "$WORK_DIR/log"

for COMPOSER_VER in ${COMPOSER_VERS[@]} ; do
  if [ ! -d "$WORK_DIR/tools/composer-$COMPOSER_VER" ]; then
    mkdir "$WORK_DIR/tools/composer-$COMPOSER_VER"
  fi
  pushd "$WORK_DIR/tools/composer-$COMPOSER_VER" >> /dev/null
    if [ ! -f "composer" ]; then
      wget https://github.com/composer/composer/releases/download/$COMPOSER_VER/composer.phar -O composer
      chmod +x composer
    fi
  popd >> /dev/null
done

echo "# Run basic tests"
phpunit8 \
  --exclude-group composer-1,composer-2 \
  --log-junit "$WORK_DIR/junit/basic.xml" \
  "${PHPUNIT_EXTRA[@]}" \
  | tee "$WORK_DIR/log/basic.log"

for COMPOSER_VER in ${COMPOSER_VERS[@]} ; do
  echo "# Run tests with composer $COMPOSER_VER"
  case "${COMPOSER_VER::1}" in
    1)
      env PATH="$WORK_DIR/tools/composer-$COMPOSER_VER:$PATH" phpunit8 \
      	--group composer-1 \
      	--log-junit "$WORK_DIR/junit/composer-$COMPOSER_VER.xml" \
	"${PHPUNIT_EXTRA[@]}" \
	| tee "$WORK_DIR/log/composer-$COMPOSER_VER.log"
      ;;
    2)
      env PATH="$WORK_DIR/tools/composer-$COMPOSER_VER:$PATH" phpunit8 \
      	--group composer-2 \
      	--log-junit "$WORK_DIR/junit/composer-$COMPOSER_VER.xml" \
	"${PHPUNIT_EXTRA[@]}"\
	| tee "$WORK_DIR/log/composer-$COMPOSER_VER.log"
      ;;
  esac
done


# echo env PATH="$PWD/extern/composer-2.5.4:$PATH" USE_TEST_PROJECT=$HOME/src/myprj DEBUG=2 phpunit8 tests/Integration/DefaultPathsTest.php
