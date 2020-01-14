#! /usr/bin/python3

import argparse
import create_archive
from fnmatch import fnmatch
import os
from os.path import basename, dirname, isdir, isfile, join
import sys

def main(argv):
    parser = argparse.ArgumentParser()
    parser.add_argument('base_directory', help='base directory')
    parser.add_argument('pattern', help='pattern (fnmatch compatible)')

    args = parser.parse_args(argv)

    if not isdir(args.base_directory):
        print("\"%s\" is not a directory" % (args.base_directory))
        sys.exit(1)

    username = '';
    password = '';
    api_key = '';
    pool_id = '';

    for root, dirs, files in os.walk(args.base_directory):
        for sub_file in files:
            if isfile(sub_file) and fnmatch(sub_file, args.pattern):
                full_path = join(root, sub_file)
                parent_dir = dirname(full_path)
                archive_name = basename(parent_dir)

                create_archive.main(['-a', archive_name, '-f', parent_dir, '-U', username, '-W', password, '-k', api_key, '-p', pool_id])

if __name__ == "__main__":
    main(sys.argv[1:])
