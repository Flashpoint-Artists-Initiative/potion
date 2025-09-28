#!/bin/bash

# This procedure is only to get the rPi5 certs for your localized DNS/SSL up and running.
# This will allow you to have a No-Internet isolated Potion instance out in the field for
# your event.

# Once this procedure is complete, you need to save all the certs from the procedure to 
# another machine so you can use them later when you do a Full build of the rpi5

sudo apt update
sudo apt upgrade
sudo apt install certbot

# You'll need to access your hosting provider for the Domain that you want this system
# to work with. In order for the system to work just as if your users are online, you'll
# need to mimic the online resource. So whereever you hosted Potion online, use the same
# DNS name
LOCAL_HOST_NAME=potion.alchemyburn.com
sudo certbot certonly --manual --preferred-challenges dns -d $LOCAL_HOST_NAME

# Certbot will create/save the certs in a letsencrypt folder