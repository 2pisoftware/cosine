#!/bin/bash

phpcbf --standard=phpcs.xml --parallel=$(nproc) --ignore="*.js,*.css,system/lib/*" system

phpcs --standard=phpcs.xml --parallel=$(nproc) --ignore="*.js,*.css,system/lib/*" system