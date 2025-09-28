The gate certs are also manually created using DNS-01 challenge. Because of this, certificates will need to be generated each year and there is no automatic mechanism for renewal. Also, Let's encrypt certs expire every 3 months, and these gate computer systems go into storage after use each year. So....we just have to do this step manually.

On an already built system, certbot is already installed. Use the following command to get a new DNS challenge:
sudo certbot certonly --manual --preferred-challenges dns -d <YOUR_DOMAIN_HERE>
