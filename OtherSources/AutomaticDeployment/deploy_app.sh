#!/bin/bash
# deploy_app.sh
# Usage example: deploy_app.sh mycoolapp 1.0.2
# Assumes the application to be packaged is within the "debian" directory

# Update the DEBIAN/control file with the latest version information
find ./debian/DEBIAN/ -name 'control' 
    -exec 
            sed -i.bak 's/Version: .*/Version: '$2'/g' {} \; 

# The above line assumes a current version of your app at the 
# current location. You might replace this with an SVN checkout 
# for example.

# Create the package. 
dpkg-deb --build debian $1-$2.deb 

# And then upload the package to our own package repository
scp $1-$2.deb
       user@exampledomain.com:/var/www/repository/packages 
# Clean up 
rm *deb
