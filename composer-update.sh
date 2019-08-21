#!/bin/bash

docker run --rm -v $(pwd):/app composer update --ignore-platform-reqs --no-scripts
