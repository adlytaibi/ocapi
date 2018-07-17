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

* Copy your host.pem and host.key certificate files to web/sslkeys

* (Optionally) Self-sign your own certificates (modify `web` to match your server)

    ```
    openssl req -x509 -nodes -newkey rsa:4096 -keyout web/sslkeys/host.key -out web/sslkeys/host.pem -days 365 -subj "/C=CA/ST=Ontario/L=Toronto/O=Storage/OU=Team/CN=web"
    ```

3. docker-compose

    ```
    docker-compose up -d
    ```

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

8. Aggregates' and aggregates' volumes' view:

  ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/aggregates.png)

9. SVMs' view:

  ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/svms.png)

10. Volumes' view at cluster and aggregate level:

  ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/volumes.png)

  ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/svms_volumes.png)

  ![](https://raw.githubusercontent.com/adlytaibi/ss/master/ocapi/aggregates_volumes.png)

11. Interfaces' view:

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

