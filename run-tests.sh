#!/bin/sh

vendor/bin/phpunit --log-junit /test-results/test-report.xml --cache-result-file /dev/null
result=$?
chmod 0666 /test-results/test-report.xml
exit $result
