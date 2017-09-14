#! /bin/bash

GIT_ARCHIVE=storiqone-backend_$(git describe | perl -pe 's/^v([^-]+).*$/$1/').tar

echo 'CLEAN'
dh_clean

echo 'UPDATE src'
git archive --format=tar -o ../${GIT_ARCHIVE} master
gzip -9vf ../${GIT_ARCHIVE}

# echo $(git describe)$(git log -1 --pretty=', %ci') > storiq_one_version

echo 'BUILD package'
dpkg-buildpackage -us -uc -rfakeroot -sa

