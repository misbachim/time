#!/bin/bash

docker run --rm -v $(pwd):/app composer install --ignore-platform-reqs --no-scripts
