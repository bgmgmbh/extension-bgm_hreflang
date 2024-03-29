#!/bin/bash

## Description: Run functional tests inside the web container
## Usage: functional-tests "^10"
## Example: `ddev functional-tests "^9"´ or  ´ddev functional-tests "^10"`

if [ -d ".Build" ]; then
  rm -r .Build
fi
if [ -f "composer.lock" ]; then
  rm composer.lock
fi
composer require typo3/minimal="$@" nimut/testing-framework="^6"

typo3DatabaseName="testdb" typo3DatabaseUsername="root" typo3DatabasePassword="root" typo3DatabaseHost="db" typo3DatabasePort="3306" TYPO3_PATH_WEB="$PWD/.Build/Web" .Build/bin/phpunit -c .Build/vendor/nimut/testing-framework/res/Configuration/FunctionalTests.xml Tests/Functional

composer remove typo3/minimal nimut/testing-framework
rm composer.lock
rm -r .Build

exit
