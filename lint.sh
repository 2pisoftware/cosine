#!/bin/bash

phpcbf --standard=phpcs.xml --parallel=$(nproc) --ignore="*.js,*.css,system/lib/*" system

phpcs --config-set ignore_warnings_on_exit 1 --standard=phpcs.xml --parallel=$(nproc) --ignore="*.js,*.css,system/lib/*" system