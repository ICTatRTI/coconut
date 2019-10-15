se strict;
use warnings;
use DBI;
use POSIX qw(strftime);
use IO::Handle;
use Email::Stuffer;
use locale;
use utf8;
binmode STDOUT, ":utf8";
binmode (STDIN,':utf8');

# This script is used to send total of direct unique participants per organization
# Created by Claudia Nunez on Oct 10th, 2016

#Variables
my $host = "107.20.181.244";
my $database = "bitnami_drupal7";
my $user = "bitnami";
my $pw = "1f413b88f8^";

#  MYSQL connection to database
my $connect = DBI->connect("dbi:mysql:dbname=$database;host=$host", $user, $pw, {
         mysql_enable_utf8 => 1,
     } );

# Query
my $filename = '/home/bitnami/automatic_emails/query_unique_direct_participants.sql';
my $query;
open(my $fh, '<:encoding(UTF-8)', $filename) or die "Could not open file '$filename' $!";
while (my $row = <$fh>) {
  #chomp $row;
  $query = $query.$row;
}

# print $query;

# Running query
my $result =  $connect->prepare($query) or die 'Prepare statement failed: $connect->errstr()';
$result->execute();

# Fetch results
my $row;
my $results = "<table><tr><th>ID</th><th>Nombre</th><th>Total Participantes Únicos directos</th></tr>";
while ($row = $result->fetchrow_hashref()) {
        $results = $results."<tr><td>$row->{provider_id}</td><td>$row->{provider_name}</td><td>$row->{total_directs}</td></tr>";
}

$results = $results."</table>";

# print $results;

# Closing database session
$result->finish();
$connect->disconnect();

# Sending email
 Email::Stuffer->from('Claudia Nunez <cnunez@alertajoven.com>')
                ->to('Virginia Vallejo <vvallejo@project.rti.org>', 'Raquel Ovalle <rovalle@project.rti.org>')
                ->cc('Claudia Nunez <cnunez@alertajoven.com>'     )
                ->subject('Reporte Participantes Únicos Directos')
                ->html_body($results)
                ->transport('SMTP', { host => 'mail.alertajoven.com' })
                ->send;

print strftime("%T-%x:::", localtime)."Query done\n";