Vagrant.configure("2") do |config|
  config.vm.box = "debian/buster64"
  config.vm.define "DreamboxReStream"
  config.vm.hostname = "dreamboxrestream.box"
  config.vm.network "public_network"
  config.ssh.forward_agent = true

  config.vm.provision "shell", path: "provision.sh"

  config.vm.network "forwarded_port", guest: 80, host: 8088, id: "nginx"
  config.vm.synced_folder "./", "/home/vagrant/dreamboxrestream", :mount_options => ["dmode=775", "fmode=664"], :owner => 'vagrant', :group => 'www-data'

  config.vm.provider "virtualbox" do |v|
   v.memory = 2048
   v.cpus = 2
  end
end
