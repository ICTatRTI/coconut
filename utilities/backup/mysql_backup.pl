use strict;
use warnings;
use MySQL::Backup;
use POSIX qw(strftime);
use IO::Handle;
use IO::Compress::Gzip qw(gzip $GzipError);

# This script is used to perform a full backtup on given database
# Created by Claudia Nunez on June 30th, 2015


# Cheking and reading parameters
my $num_args = $#ARGV + 1;

if ($num_args != 1) {
    print "\n\tUsage: mysql_backup.pl database_name\n\n";
    exit;
}

my $db_name = $ARGV[0];
my $path = "/home/bitnami/stack/mysql/backup/${db_name}_bk";
my $date = strftime "%F", localtime;
my $bk_schema_file_name = "${path}/bk_schema_${date}";
my $bk_data_file_name = "${path}/bk_data_${date}";

print strftime("%T-%x:::", localtime)."Backing up database $db_name \n";

# Creating and opening file descriptors to store backups
print strftime("%T-%x:::", localtime)."Creating file descriptors for $bk_schema_file_name and $bk_data_file_name\n";
open(my $bk_schema_descriptor, '>:encoding(UTF-8)', $bk_schema_file_name) or die "Could not open file ${bk_schema_file_name}";
$bk_schema_descriptor->autoflush(1);

# Creating backup
my $mb = new MySQL::Backup($db_name,'107.20.181.244','bitnami','1f413b88f8^',{'USE_REPLACE' => 1, 'SHOW_TABLE_NAMES' => 1});
print $bk_schema_descriptor $mb->create_structure();
`mysqldump ${db_name} --password=1f413b88f8^ --host=107.20.181.244 --default-character-set=latin1  -r $bk_data_file_name`;
print strftime("%T-%x:::", localtime)."Backup files succesfully generated\n";

# Closing files
close $bk_schema_descriptor;

# Compressing files
gzip $bk_schema_file_name => "${bk_schema_file_name}.gz" or die "gzip failed for $bk_schema_file_name: $GzipError\n";
gzip $bk_data_file_name => "${bk_data_file_name}.gz" or die "gzip failed $bk_data_file_name: $GzipError\n";
print strftime("%T-%x:::", localtime)."Backup files succesfully gzipped\n";

# Removing temporary files
`rm $bk_schema_file_name $bk_data_file_name`;

# Removing backup files older then 10 days
print strftime("%T-%x:::", localtime)."Removing backup files older then 10 days\n";
`find ${path}/bk* -mtime \+10 -exec rm {} \\;`;

print strftime("%T-%x:::", localtime)."Backing up finished\n";
