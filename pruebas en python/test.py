import SOAPpy
SOAPpy.Config.SSL.cert_file = "/home/nlattessi/Dropbox/code/htdocs/PHP/synchro/cert.pem"
SOAPpy.Config.SSL.key_file = "/home/nlattessi/Dropbox/code/htdocs/PHP/synchro/key.pem"

server = SOAPpy.SOAPProxy("https://190.111.249.189/mantis/api/soap/mantisconnect.php?wsdl")

print server
