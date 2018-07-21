# OnCommand API Services: Representation of cDOT clusters

This is a php-based code that provides a web-interface to show a list of cDOT cluster, SVMs, Aggregates, Volumes and LIFs.
The workflow is simple, you provide login credentials and API endpoint. RESTful API calls are made on your behalf and the result is presented in a table with pagination, sort and filter features. The data collected from OnCommand API Services is cached and refreshed every 10 minutes.

  ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/clusters.png)

In the event of lack communication with OnCommand API Services, the cached data is displayed with a warning message and the option to work offline (In case this application is running on a laptop).

  ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/clusters_offline.png)

## Pre-requisites

* git
* docker
* docker-compose
* OnCommand API Services fully configured with OCUM

## Installation

1. Clone this:

    ```
    git clone https://github.com/adlytaibi/ocapi
    ```

    ```
    cd ocapi
    ```

2. SSL certificates

    ```
    mkdir web/sslkeys
    ```

    1. Self-sign your own certificates: (modify `web` to match your server)

        ```
        openssl req -x509 -nodes -newkey rsa:4096 -keyout web/sslkeys/host.key -out web/sslkeys/host.pem -days 365 -subj "/C=CA/ST=Ontario/L=Toronto/O=Storage/OU=Team/CN=web"
        ```

    2. Or sign your SSL certificate with a CA:

        1. Create private key, generate a certificate signing request

            ```
            openssl genrsa -out web/sslkeys/host.key 2048
            ```

        2. Create a Subject Alternate Name configuration file `san.cnf` in 'web/sslkeys'

            ```
            [req]
            distinguished_name = req_distinguished_name
            req_extensions = v3_req
            prompt = no
            default_md = sha256
            [req_distinguished_name]
            C = CA
            ST = Ontario
            L = Toronto
            O = Storage
            OU = Storage
            CN = ocapi
            [v3_req]
            keyUsage = keyEncipherment, dataEncipherment
            extendedKeyUsage = serverAuth
            subjectAltName = @alt_names
            [alt_names]
            DNS.1 = ocapi
            DNS.2 = ocapi.acme.net
            IP.1 = 1.2.3.4
            ```

        3. Generate a certificate signing request

            ```
            cd web/sslkeys/
            openssl req -new -sha256 -nodes -key host.key -out ocapi.csr -config san.cnf
            ```

        4. In your CA portal use the `ocapi.csr` output and the following SAN entry to sign the certificate, you should get a `certnew.pem` that can be saved as `host.pem`

            ```
            san:dns=ocapi.acme.net&ipaddress=1.2.3.4
            ```

        5. Copy your `host.pem` certificate files to `web/sslkeys`

3. docker-compose

    ```
    docker-compose up -d
    ```

    * Note: This container uses docker's bridge networking by default. When running this container on the OnCommand API Services server, the line `network_mode: host` in `docker-compose.yml` should be uncommented. Also, keep in mind that this container is configured by default to listen on port 443. Therefore, the OnCommand API Services server should be listening on port 8443 for example.

4. The login page can be accessed using the URL below:

    ```
    https://<IP_address>
    ```
    (or if accessing from the same guest https://localhost)

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/endpoint_unset.png)

5. Enter the API endpoint and credentials already setup on OnCommand API Services with a minimum role of 'Operator'

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/endpoint_entry.png)

6. Sort, filter, pagination throughout all pages. Clusters' view:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/clusters.png)

7. Nodes' view:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/cluster_nodes.png)

8. Aggregates' view:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/aggregates.png)

9. SVMs' view:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/svms.png)

10. Volumes' view at the cluster level:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/volumes.png)

11. Volumes' view at an SVM level:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/svms_volumes.png)

12. Volumes' view at an aggregate level:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/aggregates_volumes.png)

13. Interfaces' view:

    ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/interfaces.png)

## Further reading
* [Docker Compose](https://docs.docker.com/compose/)
* [Apache](https://httpd.apache.org/)
* [PHP](http://www.php.net/)
* [DataTables](https://datatables.net/)
* [Bootstrap](https://getbootstrap.com/)
* [jQuery](https://jquery.com/)

## Notes
This is not an official NetApp repository. NetApp Inc. is not affiliated with the posted examples in any way.

