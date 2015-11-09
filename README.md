# Sentinel - PHP

Simple application that ping and save the response time of several urls. These times are shown on a graph updated in real time.

![Image of Sentinel](https://framapic.org/HB3zFB3iGLmr/Gg69D6FX)

## Requirements

- PHP 5 >= 5.3.0

## Getting started

#### Composer

```
git clone https://github.com/spotlab/sentinel.git sentinel
cd sentinel
composer install
```

#### Create websites.yml

```
websites:
  medococean:
    title: "MédocOcean"
    url: "http://www.medococean.com/api/content/ts/medococean_v2/5fcdb1e3ec5fc7c8669f25a557d1da9f"
    method: "POST"
    content:
        randomSeed: "21cc3553-bd4d-4745-a6d4-5bfa2f182b9e"
        start: 0
        size: 15
        appType: "sentinel"
    header:
      - "Cache-Control:no-cache"
      - "Content-Type:application/json;charset=UTF-8"
      - "Authorization:Basic bWVkb2NvY2Vhbl93ZWJzaXRlOjA4OTFkMGJl"
  reunion:
    title: "Réunion"
    url: "http://www.reunion.fr/api/content/ts/reunion_v2/9854b2d6d91f2b2e2d512f6fd086820c"
    method: "POST"
    content:
        randomSeed: "21cc3553-bd4d-4745-a6d4-5bfa2f182b9e"
        start: 0
        size: 15
        appType: "sentinel"
    header:
      - "Cache-Control:no-cache"
      - "Content-Type:application/json;charset=UTF-8"
      - "Authorization:Basic cmV1bmlvbl93ZWJzaXRlIDo5ZjRmNWEzMA=="
```

#### Start backup command

```
bin/sentinel ping websites.yml
```

## Contributing

Format all code to PHP-FIG standards.
http://www.php-fig.org/

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
