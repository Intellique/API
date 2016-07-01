#!/usr/bin/perl

use strict;
use warnings;
use 5.10.0;
use Getopt::Long;
use JSON::XS;
use LWP::UserAgent;
use Term::ReadKey;
use Data::Dumper;

my $option;

Getopt::Long::Configure('no_ignore_case');
unless (
    GetOptions(
        'help|h'       => \$option->{help},
        'verbose|v'    => \$option->{verbose},
        'username|U=s' => \$option->{username},
        'password|W=s' => \$option->{password},
        'api-key|k=s'  => \$option->{apikey},
        'hostname|H=s' => \$option->{hostname},
    )
  )
{
    die "Fatal: invalid option \n";
}

if ( $option->{help} ) {
    print <<EOL
$0 options:
		    --help | -h 	show this help
		 --verbose | -v  	verbose

		--hostname | -H 	host to connect to (required)
		 --api-key | -k		API key to use (required)
		--username | -U 	username (required)
		--password | -W 	password

		 --archive | -a		archive name to search
		 --arch-id | -A 	archive ID to display
	    --metadata | -m		metadata to search for
		
EOL
      ;
    exit;
}

foreach my $param (qw(username apikey hostname)) {
    die "required option: $param\n" if not $option->{$param};
}

if ( not $option->{password} ) {
    ReadMode('noecho');
    print "Enter Password: ";
    chomp( $option->{password} = <STDIN> );
    ReadMode('restore');
    say '';
}

my $ua     = LWP::UserAgent->new;

$ua->ssl_opts( verify_hostname => 0 );

my $credentials = encode_json(
    {
        'login'    => $option->{username},
        'password' => $option->{password},
        'apikey'   => $option->{apikey}
    }
);

say $credentials if $option->{verbose};

# Authentication
my $request =
  HTTP::Request->new(
    POST => "https://$option->{hostname}/storiqone-backend/api/v1/auth/" );
$request->content_type('application/json');
$request->content($credentials);

my $result = $ua->request($request);
if ( $result->is_success ) {
    say $result->decoded_content;
} else {
    die "Error: "
      . $result->decoded_content . "\n"
      . $result->status_line . "\n";
}

# Save cookie
my ( $cookie ) = ($result->header('Set-Cookie') =~ m((PHPSESSID=\w+);));
say $cookie if $option->{verbose} ;

# list archives
$request =
  HTTP::Request->new(
    GET => "https://$option->{hostname}/storiqone-backend/api/v1/archive/" );
$request->content_type('application/json');
$request->header('Cookie' => $cookie);

$result = $ua->request($request);
if ( $result->is_success ) {
    say $result->decoded_content;
} else {
    die "Error: "
      . $result->decoded_content . "\n"
      . $result->status_line . "\n";
}
