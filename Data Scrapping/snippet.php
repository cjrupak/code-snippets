<?php

	namespace Scrappers\App;

	use GuzzleHttp\Client as GuzzleClient;
	use GuzzleHttp\Pool;
	use GuzzleHttp\Psr7\Request;
	use GuzzleHttp\Psr7\Response;

	/**
	 *
	 */
	class Stockx {

		public function getBrandsAndUrl( $site, $action = false ) {

			$time_start = microtime( true );

			$brands = array(
				"adidas"       => "Adidas",
				"air jordan"   => "Air Jordan",
				"nike"         => "Nike",
				"other brands" => "Other Brands",
				"release_date" => "New Release",
			);

			Stockx::getAllUrls( $brands, $site, $time_start, $action );
		}

		public function getAllUrls( $brands, $site, $time_start, $action = false ) {

			$host = "https://" . $site . ".com";

			if ( session_status() == PHP_SESSION_NONE ) {
				session_start();
			}

			if ( ! empty( $_SESSION[ $host ] ) ) {

				unset( $_SESSION[ $host ] );
			}

			foreach ( $brands as $brand => $label ) {

				if ( $brand == "release_date" ) {
					$tag = "sort";
				} else {
					$tag = "_tags";
				}

				$url = $host . "/api/browse?" . $tag . "=" . $brand;

				$guzzle = new GuzzleClient( [ 'http_errors' => false ] );

				$request = $guzzle->get( $url );

				$data = $request->getBody()->getContents();

				$data = json_decode( $data );

				$last_page = $data->Pagination->lastPage;

				$iterator = function () use ( $guzzle, $brand, $last_page, $host, $data, $tag ) {

					$index = 0;

					while( true ) {

						if ( $index > $last_page ) {
							break;
						}

						$url     = $host . '/api/browse?' . $tag . '=' . $brand . '&productCategory=' . $data->Products[0]->productCategory . '&page=' . $index ++ . '&sort=release_date&order=DESC';
						$request = new Request( "GET", $url, [] );
						yield $guzzle
							->sendAsync( $request )
							->then( function ( Response $response ) use ( $request ) {
								return [ $request, $response ];
							} );
					}
				};

				$brand_not_require = array(

					"Air Jordan x Levis",
					"Carhartt WIP",
					"Complexcon",
					"Mastermind",
					"ComplexCon",
					"Extra Butter",
					"Fear of God",
					"MRC Noir",
					"Nikelab x MMW",
					"Polo",
					"TDE",
					"Converse",
					"The North Face",
					"Undefeated"

				);

				$promise = \GuzzleHttp\Promise\each_limit(
					$iterator(),
					10,  /// concurrency,
					function ( $result, $index ) use ( $host, $brand_not_require, $guzzle, $action ) {
						/** @var GuzzleHttp\Psr7\Request $request */

						list( $request, $response ) = $result;

						$data = $response->getBody()->getContents();

						$record = json_decode( $data );

						$record = $record->Products;

						foreach ( $record as $key => $value ) {

							$value = (array) $value;

							if ( in_array( $value["brand"], $brand_not_require ) ) {

								continue;
							}

							if ( $action == "update" ) {

								if ( date( "F Y" ) !== date( "F Y", strtotime( $value["releaseDate"] ) ) ) {

									continue;
								}

								$shoe_data = array(
									"site"      => "stockx",
									"shoe_data" => array(
										"name"  => str_replace( '"', "'", $value["title"] ),
										"title" => str_replace( '"', "'", $value["title"] ) . " " . $value["styleId"],
										"url"   => stripslashes( $host . '/' . $value["urlKey"] ),
										"brand" => $value["brand"],
										"color" => stripslashes( $value["colorway"] ),
										"style" => $value["styleId"],
										"image" => stripslashes( $value["media"]->imageUrl )
									)
								);

								$request  = new \GuzzleHttp\Psr7\Request( 'post', 'http://142.93.220.155/scrapper-shoes/wp-json/cjaddons/addon-client-deadstock/add-shoes', [ 'content-type' => 'application/json' ], json_encode( $shoe_data ) );
								$response = $guzzle->send( $request );
								print_r( (string) $response->getBody() );
							} else {

								$_SESSION[ $host ][] = array(

									"name"  => str_replace( '"', "'", $value["title"] ),
									"title" => str_replace( '"', "'", $value["title"] ) . " " . $value["styleId"],
									"url"   => stripslashes( $host . '/' . $value["urlKey"] ),
									"brand" => $value["brand"],
									"color" => stripslashes( $value["colorway"] ),
									"style" => $value["styleId"],
									"image" => stripslashes( $value["media"]->imageUrl )
								);
							}
						}
					}
				);

				$promise->wait();
			}

			if ( $action == "update" ) {

				exit;
			}

			$json = stripslashes( json_encode( $_SESSION[ $host ] ) );

			file_put_contents( dirname( dirname( __FILE__ ) ) . '/result/' . str_replace( ".com", "", parse_url( $host )["host"] ) . '.json', $json );

			$time_end = microtime( true );

			if ( round( number_format( $time_end - $time_start, 2, '.', '' ) ) < 60 ) {
				echo "Time Taken to crawl " . $host . " " . number_format( $time_end - $time_start, 2, '.', '' ) . " Secs";
			} else {
				echo "Time Taken to crawl " . $host . " " . number_format( ( $time_end - $time_start ) / 60, 2, '.', '' ) . " Mins";
			}

			// else{

			// 	file_put_contents(dirname(dirname(__FILE__)) . '/result/'. str_replace(".com", "", parse_url($host)["host"]) . '-error.log', $_SESSION[$host]["error"]);
			// }

			exit;
		}

	}