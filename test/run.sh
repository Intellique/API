#! /bin/bash

cd "$(dirname "$0")"
python3 -m unittest discover -p '*_test.py'

