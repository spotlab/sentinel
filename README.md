# Sentinel - PHP / MongoDB

Simple application that ping and save the response time of several urls. These times are shown on a graph updated in real time.

![Image of Sentinel](https://framapic.org/LhlMezPOF0av/6cBRKWeYZxC4.png)

## Requirements

- PHP 5 >= 5.3.0
- MongoDB

## Getting started

#### Composer

```
git clone https://github.com/spotlab/sentinel.git sentinel
cd sentinel
composer install
```

#### Create config/index.yml

```
---
parameters:
    company: Spotlab
    mongo: mongodb://<username>:<password>@xxxxxxx.mongolab.com:41683/sentinel

projects:
    drupal6:
        title: Drupal 6
        projects:
            homepages:
                title: Homepages
                series: homepages_d6.yml
    drupal7:
        title: Drupal 7
        projects:
            homepages:
                title: Homepages
                series: homepages_d7.yml
            playlists:
                title: Playlists
                series: playlists.yml
            socialwall:
                title: Socialwall
                series: socialwall.yml
    api:
        title: API
        projects:
            playlists:
                title: Playlists
                series: api.yml
```

#### Create config/playlists.yml


```
---
series:
    medococean:
        title: MédocOcean
        url: http://www.medococean.com/api/content/ts/medococean_v2/5fcdb1e3ec5fc7c8669f25a557d1da9f
        method: POST
        headers:
            Cache-Control: no-cache
            Content-Type: application/json;charset=UTF-8
            Authorization: Basic cHVibGljX21lZG9jb2NlYW5fd2Vic2l0ZTpmODUxNjRkMGNjMTZiY2NkODM0NjRjNzYwN2ZkYmZlMw==
    reunion:
        title: Réunion
        url: http://www.reunion.fr/api/content/ts/reunion_v2/9854b2d6d91f2b2e2d512f6fd086820c
        method: POST
        headers:
            Cache-Control: no-cache
            Content-Type: application/json;charset=UTF-8
            Authorization: Basic cHVibGljX3JldW5pb25fd2Vic2l0ZTo0M2VjODM5MTFhNjdiNTE5ZWRhZDExZDcxMDJlMTY4Mw==
    valleedordogne:
        title: Vallée Dordogne
        url: http://www.vallee-dordogne.com/api/content/ts/valleedordogne_v2/30e3d142ffd8b8b62aa1ded047baa40a
        method: POST
        headers:
            Cache-Control: no-cache
            Content-Type: application/json;charset=UTF-8
            Authorization: Basic cHVibGljX3ZhbGxlZWRvcmRvZ25lX3dlYnNpdGU6MTQ3NTQyYTk1YmZhMjEzMDExMWQwOWUzZjZkOGE0MjE=
    tourismelot:
        title: Tourisme Lot
        url: http://www.tourisme-lot.com/api/content/ts/adt_lot_v2/80d74c2a7c4588e22988fbf82aedc4af
        method: POST
        headers:
            Cache-Control: no-cache
            Content-Type: application/json;charset=UTF-8
            Authorization: Basic cHVibGljX2FkdF9sb3Rfd2Vic2l0ZTo4N2U3NTRiMTE0ZWNjNDMxODI2NDZkYTI5YTZjMTVlMA==
    beaune:
        title: Beaune
        url: http://www.beaune-tourisme.fr/api/content/ts/beaune_v2/3d35f5dc87b4ffcbc6c5737c1a031fdc
        method: POST
        headers:
            Cache-Control: no-cache
            Content-Type: application/json;charset=UTF-8
            Authorization: Basic cHVibGljX2JlYXVuZV93ZWJzaXRlOmQwYTliZGFmMzc3ZDhiZjQ4ZjAyZjJmNjQ1MDY0OGI4


```

#### Start ping command

```
bin/sentinel ping
```

#### Start php server

```
php -S 127.0.0.1:8001 -t web
```

## Contributing

Format all code to PHP-FIG standards.
http://www.php-fig.org/

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
