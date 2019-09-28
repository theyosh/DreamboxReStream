
Vagrant.configure("2") do |config|
  config.vm.box = "generic/ubuntu1804"
  config.vm.define "DreamboxReStream"
  config.vm.hostname = "dreamboxrestream.box"
  config.vm.network "public_network"
  config.ssh.forward_agent = true

  config.vm.provision :shell, :inline => "apt-get -y update && apt-get -y install git nginx php-fpm php-cli php-mbstring php-xml php-sqlite3 ffmpeg"
  config.vm.provision :shell, :inline => "usermod -G vagrant -a  www-data"
  config.vm.provision :shell, :inline => "rm /etc/nginx/sites-enabled/default || true"
  config.vm.provision :shell, :inline => "ln -s /home/vagrant/dreamboxrestream/nginx.conf /etc/nginx/sites-enabled/dreamboxrestream || true"

  config.vm.network "forwarded_port", guest: 80, host: 8088, id: "nginx"
  config.vm.synced_folder "./", "/home/vagrant/dreamboxrestream", :mount_options => ["dmode=775", "fmode=664"]

  config.vm.provider "virtualbox" do |v|
   v.memory = 2048
   v.cpus = 2
  end

end
