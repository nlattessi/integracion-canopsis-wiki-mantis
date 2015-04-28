from SOAPpy import WSDL

wsdl = "https://stss.synchro-technologies.com/mantis/api/soap/mantisconnect.php?wsdl"

SOAPpy.Config.SSL.cert_file = 'cert.pem'
SOAPpy.Config.SSL.key_file = 'key.pem'

server = WSDL.Proxy(wsdl, config=config)



from SOAPpy import WSDL

url = 'http://www.pascalbotte.be/rcx-ws/rcx'

# just use the path to the wsdl of your choice
wsdlObject = WSDL.Proxy(url + '?WSDL')

print 'Available methods:'
for method in wsdlObject.methods.keys() :
  print method
  ci = wsdlObject.methods[method]
  # you can also use ci.inparams
  for param in ci.outparams :
    # list of the function and type
    # depending of the wsdl...
    print param.name.ljust(20) , param.type
  print
