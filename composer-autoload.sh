#!/bin/bash

docker run --rm -v $(pwd):/app composer dump-autoload
