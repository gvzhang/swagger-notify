#!/bin/bash
location="/home/vagrant/Code/API_HG_Business_Doc_Old/HG_Business"
find $location -mtime +1 -type f -iregex ".*\.\(json\|html\)$" |xargs rm -f