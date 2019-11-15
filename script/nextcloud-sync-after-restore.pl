#! /usr/bin/perl

use strict;
use warnings;

use JSON::PP;

if ( @ARGV and $ARGV[0] eq 'config' ) {
    my $sent = {
        'name'        => 'Nextcloud sync post restore',
        'description' => 'Update Nextcloud after restoring files',
        'type'        => 'post job'
    };
    print encode_json($sent);
    exit;
}

my $data_in       = do { local $/; <STDIN> };
my $data          = decode_json $data_in;
my $restored_path = $data->{'restore path'};

# debug stuff
open( my $fd, '>', sprintf( '/tmp/%s.json', $data->{'archive'}->{'name'} ) );
print {$fd} $data_in;
close $fd;

#log stuff
open( $fd, '>', sprintf( '/tmp/%s.log', $data->{'archive'}->{'name'} ) );
#

my $command = '/var/www/nextcloud/occ';

# Don't put trailing '/'
my $next_cloud_data_dir = '/var/www/nextcloud/data';

unless ($<) {
    my @new_gid = getgrnam 'www-data';
    $( = $) = $new_gid[2];
    my $new_uid = getpwnam 'www-data';
    $< = $> = $new_uid;
}

my $message = '';

if ( -x $command and ( !defined($restored_path) or -d $restored_path ) ) {
    my $nb_total_files = 0;
    my $nb_file_done   = 0;

    for my $vol ( @{ $data->{archive}->{volumes} } ) {
        $nb_total_files += scalar @{ $vol->{files} };
    }

    for my $vol ( @{ $data->{archive}->{volumes} } ) {
        for my $file ( @{ $vol->{files} } ) {
            next unless defined $file->{file}{'restored to'};
            my $sub = substr( $file->{file}->{'restored to'},
                length($next_cloud_data_dir) );
            print $fd "FILE rest to:$sub\n";
            my $occ_output = qx($command files:scan --path="$sub")
              or die "Failed to execute \"$command\": $?";

            $nb_file_done++;

            my $sent = {
                'finished'    => JSON::PP::false,
                'data'        => {},
                'progression' => $nb_file_done / $nb_total_files,
                'message'     => ''
            };
            print encode_json($sent);

        }
    }

} else {
    $message = "Restore path NOT FOUND $restored_path\n";
}

my $sent = {
    'finished' => JSON::PP::true,
    'data'     => {},
    'message'  => $message
};
print encode_json($sent);
