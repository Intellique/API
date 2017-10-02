#! /usr/bin/perl

use strict;
use warnings;

use JSON::PP;

my $data_in = do { local $/; <STDIN> };
my $data = decode_json $data_in;
my $restored_path = $data->{'restore path'};

# debug stuff
open( my $fd, '>', sprintf( '/tmp/%s.json', $data->{'archive'}->{'name'} ));
print { $fd } $data_in;
close $fd;

my $command = '/var/www/nextcloud/occ';
# Don't put trailing '/'
my $next_cloud_data_dir = '/var/www/nextcloud/data';

unless ($<) {
	my $new_uid = getpwnam 'www-data';
	$< = $> = $new_uid;
}

if ( -x $command and -x $restored_path ) {
	# $sub must start with '/'
	my $sub = substr($restored_path, length($next_cloud_data_dir));

	exec($command, 'files:scan', "--path=$sub") or die "Failed to execute \"$command\"";
}

