import requests
from suds.transport.http import HttpAuthenticated
from suds.transport import Reply, TransportError

class RequestsTransport(HttpAuthenticated):
    def __init__(self, **kwargs):
        self.cert = kwargs.pop('cert', None)
        # super won't work because not using new style class
        HttpAuthenticated.__init__(self, **kwargs)

    def open(self, request):
        """
        Fetches the WSDL using cert.
        """
        self.addcredentials(request)
        resp = requests.get(request.url, data=request.message,
                             headers=request.headers, cert=self.cert)
        result = io.StringIO(resp.content.decode('utf-8'))
        return result

    def send(self, request):
        """
        Posts to service using cert.
        """
        self.addcredentials(request)
        resp = requests.post(request.url, data=request.message,
                             headers=request.headers, cert=self.cert)
        result = Reply(resp.status_code, resp.headers, resp.content)
        return result

headers = {"Content-TYpe" : "text/xml;charset=UTF-8",
           "SOAPAction" : ""}
t = RequestsTransport(cert='cert.pem')
client = Client('?wsdl', location='https://stss.synchro-technologies.com/mantis/api/soap/mantisconnect.php', headers=headers,
                transport=t)
