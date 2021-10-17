.PHONY: up recompile urls sync-vendor
.DEFAULT_GOAL: up

up:
	vagrant up

recompile:
	vagrant ssh -c 'cd ~www-data/magento/vendor/solvedata/plugins-magento2/docker; exec ./tools.sh recompile'

urls:
	vagrant ssh -c 'cd ~www-data/magento/vendor/solvedata/plugins-magento2/docker; exec ./tools.sh urls'

sync-vendor:
	vagrant ssh -c 'cd ~www-data/magento; tar --exclude=vendor/solvedata -czf /tmp/vendor.tar.gz vendor/'

	vagrant ssh-config > /tmp/vagrant-ssh-config
	scp -r -F /tmp/vagrant-ssh-config default:/home/www-data/magento/vendor.tar.gz /tmp/vendor.tar.gz

	rm -rf ../vendor
	tar xzf /tmp/vendor.tar.gz --directory ../

	rm /tmp/vagrant-ssh-config
	rm /tmp/vendor.tar.gz
	vagrant ssh -c 'rm /tmp/vendor.tar.gz'
