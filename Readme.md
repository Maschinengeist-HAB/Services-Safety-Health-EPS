# Services::Safety::Health::EPS
MQTT Gateway to the german emergency pharmacy service (EPS, Notdienstsuche)

## Service description
[Aponet](https://www.aponet.de/) is the official german pharmacy portal and provides a [search](https://www.aponet.de/apotheke/notdienstsuche) for the next <abbr title="Emergency Pharmacy Service" lang="en">EPS</abbr> pharmacy. `Services::Safety::Healt::EPS` brings this search to <abbr>MQTT</abbr>.

## Configuration
The service uses a set of environment variables for configuration in the Dockerfile

### Connection and environment

| Variable           | Usage                                                                          | Default value                                      |
|--------------------|--------------------------------------------------------------------------------|----------------------------------------------------|
| `MQTT_HOST`        | Specifies the MQTT broker host name                                            | `message-broker`                                   |
| `MQTT_PORT`        | Specifies the MQTT port                                                        | `1883`                                             |
| `MQTT_USERNAME`    | Username for the MQTT connection                                               | none                                               |
| `MQTT_PASSWORD`    | Password for the MQTT connection                                               | none                                               |
| `MQTT_KEEP_ALIVE`  | Keep alive the connection to the MQTT broker every *n* seconds                 | `120`                                              |
| `MQTT_BASE_TOPIC`  | MQTT base topic, will prepend to the defined topics, i.e. `base_topic/command` | `maschinengeist/services/safety/health/eps`        |
| `MQTT_LWT_TOPIC`   | MQTT last will and testament topic, i.e. `base_topic/lwt`                      | `maschinengeist/services/safety/health/eps/lwt`    |
| `MQTT_ERROR_TOPIC` | Error messages will be published to this topic, i.e. `base_topic/errors`       | `maschinengeist/services/safety/health/eps/errors` |
| `TZ`               | Timezone                                                                       | `Europe/Berlin`                                    |
| `VERBOSE`          | Verbose output                                                                 | `false`                                            |

### Service default configuration

| Variable            | Usage                               | Default value           |
|---------------------|-------------------------------------|-------------------------|
| `EPS_URI`           | <abbr>URI</abbr> to the EPS website | `https://www.aponet.de` |
| `DEFAULT_LATITUDE`  | Latitude to search for              | `51.9117`               |
| `DEFAULT_LONGITUDE` | Longitude to search for             | `8.8394`                |


## How to pull and run this image
Pull this image by

    docker pull ghcr.io/maschinengeist-hab/services-safety-health-eps:latest

Run this image by

    docker run -d --name mg-eps-service ghcr.io/maschinengeist-hab/services-safety-health-eps:latest

## How to request a result

By default, the service does just nothing but runs and waits for a request on `MQTT_BASE_TOPIC/command`. The least arguments needed are
`command` and `vars`.

    {
        "command":"update",
        "vars" : []
    }

This request would be requesting the geographical nearest EPS pharmacy to the default latitude and longitude (`DEFAULT_LATITUDE` and `DEFAULT_LONGITUDE`)

    {
       "results":{
          "count":1,
          "data":[
             {
                "name":"Aesculap Apotheke",
                "kammer":"akwl",
                "id":"1119001",
                "apo_id":"akwl1119001",
                "strasse":"Mittelstr. 25",
                "plz":"32657",
                "ort":"Lemgo",
                "distanz":"13.68109534964",
                "longitude":"8.90397",
                "latitude":"52.02801",
                "telefon":"05261\/3727",
                "fax":"05261\/16700",
                "email":"info@aesculap-lemgo.de",
                "startdatum":"06.11.2023",
                "startzeit":"09:00",
                "enddatum":"07.11.2023",
                "endzeit":"09:00"
             }
          ]
       }
    }

To set a custom location a request needs vars

```
{
   "command": "update",
   "vars":{
      "longitude": 12.22,
      "latitude": 49.03
   }
}
```

will result in 

```
    {
       "results":{
          "count":1,
          "data":[
             {
                "name":"Adler-Apotheke",
                "kammer":"blak",
                "id":"1337",
                "apo_id":"blak1337",
                "strasse":"Sudetenstr. 34",
                "plz":"93073",
                "ort":"Neutraubling",
                "distanz":"4.51858048665",
                "longitude":"12.1963587",
                "latitude":"48.9924629",
                "telefon":"09401 \/ 1054",
                "fax":"09401 \/ 1050",
                "email":[
                   
                ],
                "startdatum":"06.11.2023",
                "startzeit":"08:00",
                "enddatum":"07.11.2023",
                "endzeit":"08:00"
             }
          ]
       }
    }
```
(As you can see, the retrieved data differ in format and completeness!)

If you are want to get more than one result, just use the `show` parameter

```
{
   "command": "update",
   "vars":{
      "longitude": 7.89,
      "latitude": 49.98,
      "show": 5
   }
}
```

to get this

```
{
   "results":{
      "count":5,
      "data":[
         {
            "name":"Blumenpark-Apotheke",
            "kammer":"lakrlp",
            "id":"21695",
            "apo_id":"lakrlp21695",
            "strasse":"Mainzer Str. 39",
            "plz":"55411",
            "ort":"Bingen",
            "distanz":"1.53077718487",
            "longitude":"7.9023325",
            "latitude":"49.9687685",
            "telefon":"06721\/16677",
            "fax":"06721\/16678",
            "email":[
               
            ],
            "startdatum":"06.11.2023",
            "startzeit":"08:30",
            "enddatum":"07.11.2023",
            "endzeit":"08:30"
         },
         {
            "name":"Rheingau Apotheke",
            "kammer":"lakh",
            "id":"613",
            "apo_id":"lakh613",
            "strasse":"Winkeler Str. 68",
            "plz":"65366",
            "ort":"Geisenheim",
            "distanz":"5.77215412308",
            "longitude":"7.9700231",
            "latitude":"49.9855233",
            "telefon":"06722\/8119",
            "fax":"06722\/8159",
            "email":[
               
            ],
            "startdatum":"06.11.2023",
            "startzeit":"08:30",
            "enddatum":"07.11.2023",
            "endzeit":"08:30"
         },
         {
            "name":"Neue Apotheke am Holzmarkt",
            "kammer":"lakrlp",
            "id":"22407",
            "apo_id":"lakrlp22407",
            "strasse":"Dessauer Str. 1",
            "plz":"55545",
            "ort":"Bad Kreuznach",
            "distanz":"14.91321926018",
            "longitude":"7.85358",
            "latitude":"49.848",
            "telefon":"0671\/28418 o.365558",
            "fax":"0671\/36558",
            "email":[
               
            ],
            "startdatum":"06.11.2023",
            "startzeit":"08:30",
            "enddatum":"07.11.2023",
            "endzeit":"08:30"
         },
         {
            "name":"Apotheke Steidle",
            "kammer":"lakrlp",
            "id":"45033",
            "apo_id":"lakrlp45033",
            "strasse":"Mainzer Str. 9",
            "plz":"55262",
            "ort":"Heidesheim",
            "distanz":"16.20374271145",
            "longitude":"8.1147048",
            "latitude":"49.9952679",
            "telefon":"06132\/4353850",
            "fax":"06132\/43538515",
            "email":[
               
            ],
            "startdatum":"06.11.2023",
            "startzeit":"08:30",
            "enddatum":"07.11.2023",
            "endzeit":"08:30"
         },
         {
            "name":"Rathaus-Apotheke",
            "kammer":"lakrlp",
            "id":"22302",
            "apo_id":"lakrlp22302",
            "strasse":"Weedengasse 3",
            "plz":"55291",
            "ort":"Saulheim",
            "distanz":"21.96228939772",
            "longitude":"8.1500973",
            "latitude":"49.876097",
            "telefon":"06732\/61660",
            "fax":"06732\/61676",
            "email":[
               
            ],
            "startdatum":"06.11.2023",
            "startzeit":"08:30",
            "enddatum":"07.11.2023",
            "endzeit":"08:30"
         }
      ]
   }
}
```


## License

    Copyright 2023 Christoph 'knurd' Morrison

    Licensed under the MIT license:

    http://www.opensource.org/licenses/mit-license.php

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.