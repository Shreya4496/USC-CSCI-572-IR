<?php
// phpinfo();
ini_set('display_errors', 1);
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;

if ($query) {
	require_once('/Applications/XAMPP/xamppfiles/htdocs/HW4-WEB/solr-php-client/Apache/Solr/Service.php');
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample');
	// $query = stripslashes($query);
	try {
		if ($_GET['rankingAlgorithm'] == "lucene") {
			$results = $solr->search($query, 0, $limit);
		} else {
			$additionalParameters = array('sort' => 'pageRankFile desc');
			$results = $solr->search($query, 0, $limit, $additionalParameters);
		}
	} catch (Exception $e) {
		die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
	}
}

?>
<html>

<head>
	<title>SOLR SEARCH</title>
	<style>
		input[type=text], select {
			width: 100%;
			padding: 12px 20px;
			margin: 8px 0;
			display: inline-block;
			border: 1px solid #ccc;
			border-radius: 4px;
			box-sizing: border-box;
		}

		input[type=submit] {
			width: 100%;
			background-color: #4CAF50;
			color: white;
			padding: 14px 20px;
			margin: 8px 0;
			border: none;
			border-radius: 4px;
			cursor: pointer;
		}

		input[type=submit]:hover {
			background-color: #45a049;
		}

		.form-div {
			border-radius: 5px;
			background-color: #f2f2f2;
			padding: 20px;
			width: 50%; margin: 0 auto;
		}

		.form-radio {
			text-align: center;
			
		}

		#lucene, #pagerank {
			margin-right: 20px;
		}

		h2 {
			margin-top: 50px;
		}
		.center-align {
			text-align: center;
		}

		.link {
				text-decoration: None;
				color:#1e71d7;
			}
			
		table {
			border: 1px solid #808080d9;
			width: 90%; margin: 0 auto;
			margin-top: 20px;
		}

		td{
			padding:2px 10px
		}
		
		td span {
			color: black;
			font-weight: bold;
		}

		#results-title {
			text-decoration: None;
			color:#1A0dab;
			font-size: 20px;
		}
		#results-title a{
			text-decoration: None;
			color:#1A0dab;
		}
		#results-title a:hover{
			text-decoration: underline;
		}
		
		#results-url {
			text-decoration: None;
			color:#1A0dab;
			font-size: 16px;
		}
		#results-url a{
			text-decoration: None;
			color:#1A0dab;
		}
		#results-url a:hover{
			text-decoration: underline;
		}
		
		
		#results-description, #results-id {
			font-size: 15px;
		}

		#results-txt {
			text-align: center;
    		margin: 20px;
		}

		.table-div {
			padding: 20px;
		}
	</style>
</head>

<body>
	<h2 class="center-align">SOLR SEARCH</h2>
	<div class="form-div">
		<form accept-charset="utf-8" method="get">
			<label for="q">Keywords</label>
			<input id="q" name="q" type="text" placeholder="Enter Query Terms Here ..." value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" required/>
			<div class="form-radio">
			<label for="lucene">Lucene</label>
			<input id="lucene" type="radio" name="rankingAlgorithm" value="lucene" 
			<?php if (isset($_REQUEST['rankingAlgorithm']) && $_REQUEST['rankingAlgorithm'] == 'lucene') {
																		echo 'checked="checked"';
																	} ?>
			checked>
			<label for="pagerank">Page Rank</label>
			<input id="pagerank" type="radio" name="rankingAlgorithm" value="pagerank" <?php if (isset($_REQUEST['rankingAlgorithm']) && $_REQUEST['rankingAlgorithm'] == 'pagerank') {
																		echo 'checked="checked"';
																	} ?>
			>
			</div>
			<input type="submit" value="Submit">
		</form>
	</div>
	<?php

	// display results
	if ($results) {
		$total = (int) $results->response->numFound;
		$start = min(1, $total);
		$end = min($limit, $total);
	?>
		<div id="results-txt">Results: <?php echo $start; ?> - <?php echo $end; ?> of <?php echo $total; ?></div>
		<ol>
		<?php
		// iterate result documents
		$csv = array_map('str_getcsv', file('/Applications/XAMPP/xamppfiles/htdocs/HW4-WEB/URLtoHTML_latimes_news.csv'));
		foreach ($results->response->docs as $doc) {
		?>		
			<table style="text-align:left;font-size:20px">
			<?php
			$id = $doc->id;
			$title = $doc->title;
			$url = $doc->og_url;
			$desc = $doc->og_description;

			if ($desc == "" || $desc == null) {
				$desc = "N/A";
			}
			if ($title == "" || $title == null) {
				$title = "N/A";
			}
			if ($url == "" || $url == null) {
				foreach ($csv as $row) {
					$cmp = "/Users/shreyagupta/Documents/USC Classes/Spring 2022/CSCI-572/HW4/LATIMES/latimes/".$row[0];
					if ($id == $cmp) {
						$url = $row[1];
						unset($row);
						break;
					}
				}
			}
			?>
			<!-- // echo "Title : <a href = '$url'>$title</a></br>";
			// echo "URL : <a href = '$url'>$url</a></br>";
			// echo "ID : $id</br>";
			// echo "Description : $desc </br></br>"; -->
			<tr>
				<td id='results-title'><span>TITLE: </span><?php echo ("<a href ='" . $url . "'>" . $title . "</a>");?> </td>
			</tr>
			<tr>
				<td id='results-url'><span>URL: </span><?php echo ("<a href ='" . $url . "'>" . $url . "</a>");?></td>
			</tr>
			<tr>
				<td id='results-id'><span>ID: </span><?php echo("$id</br>");?></td>
			</tr>
			<tr>
				<td id='results-description'><span>DESCRIPTION: </span><?php echo("$desc</br>");?></td>
			</tr>
		</table>
	<?php
			}
	?>
	</ol>
	<?php
	}
	?>
	</div>
</body>

</html>