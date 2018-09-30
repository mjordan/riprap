# Filesystem example for Riprap plugin development.

Intended to illustrate how plugins work.

## Data

Three simple text files, `file1.bin`, 'file2.bin`, `file3.bin'.

## Plugins

### fetchresourcelist

Every time we run Riprap, we check all files in `resources/filesystemexample/resourcefiles`. Plugin does a glob() to get all the files.

### fetchdigest

SHA-1 digests for all three files are listed in `resources/filesystemexample/manifest-sha1.txt`.

### persist

Persit results of fixity check events in a file `/tmp/riprap_filesystemexample.csv`.
