<?php
$fromCurrency = 'BTC';
$toCurrency = 'USD';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromCurrency = $_POST['from_currency'];
    $toCurrency = $_POST['to_currency'];
    $amount = $_POST['amount'];

    $apiUrl = "https://api.blockchain.com/v3/exchange/tickers/$fromCurrency-$toCurrency";
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    $result = $amount * $data['last_trade_price'];
}

$chartType = "market-price";
$url = "https://api.blockchain.info/charts/$chartType?timespan=4years&format=json";
$data = json_decode(file_get_contents($url), true);
$dataPoints = $data['values'];

// Reduce the number of data points to 23 for better readability
$step = ceil(count($dataPoints) / 23);
$chartData = [];
for ($i = 0; $i < count($dataPoints); $i += $step) {
    $chartData[] = $dataPoints[$i]['y'];
}

// Add the latest price as a separate point
$latestPrice = end($dataPoints)['y'];
$chartData[] = $latestPrice;
?>

<!DOCTYPE html>
<html>

<head>
    <title>API of blockchain.info</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        table {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h3>This website is testing some APIs from <a href="https://www.blockchain.info">blockchain.info</a></h3>

    <br><br><br>

    <h1>Average USD market Price Across Major Bitcoin Exchanges(4 Years)</h1>
    <canvas id="myChart" style="width:100%;max-width:600px"></canvas>

    <br><br><br>

    <h1>Convert Currencies(Demo)</h1>
    <table id="cryptoTable">
        <thead>
            <tr>
                <th>From (Crypto)</th>
                <th>To (Fiat)</th>
                <th>Amount</th>
                <th>Result</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <form method="post" action="">
                <tr>
                    <td>
                        <select name="from_currency">
                            <option value="BTC" <?php if ($fromCurrency === 'BTC') echo 'selected'; ?>>Bitcoin (BTC)</option>
                            <option value="ETH" <?php if ($fromCurrency === 'ETH') echo 'selected'; ?>>Ethereum (ETH)</option>
                            <option value="XRP" <?php if ($fromCurrency === 'XRP') echo 'selected'; ?>>Ripple (XRP)</option>
                        </select>
                    </td>
                    <td>
                        <select name="to_currency">
                            <option value="USD" <?php if ($toCurrency === 'USD') echo 'selected'; ?>>US Dollar (USD)</option>
                            <option value="EUR" <?php if ($toCurrency === 'EUR') echo 'selected'; ?>>Euro (EUR)</option>
                            <option value="GBP" <?php if ($toCurrency === 'GBP') echo 'selected'; ?>>British Pound (GBP)</option>
                            <!-- Add more options for other fiat currencies -->
                        </select>
                    </td>
                    <td>
                        <input type="number" name="amount" value="<?php echo $amount; ?>" step="0.01" required>
                    </td>
                    <td>
                        <input type="number" name="result" value="<?php echo $result; ?>" disabled>
                    </td>
                    <td>
                        <input type="submit" value="Convert">
                    </td>
                </tr>
            </form>
        </tbody>
    </table>

    <br><br><br>

    <h1>Cryptocurrency Information</h1>
    <table id="cryptoTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Volume</th>
                <th>Price (24-Hr)</th>
            </tr>
        </thead>
        <tbody id="cryptoData">
            <!-- Data will be dynamically added here -->
        </tbody>
    </table>

    <br><br><br>

    <h1>Block Information (Example)</h1>
    <table id="blockTable">
        <thead>
            <tr>
                <th>Property</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody id="blockData">
            <!-- Data will be dynamically added here -->
        </tbody>
    </table>

    <br><br><br>

    <h1>Transactions Information (First ten tx from the block)</h1>
    <table id="transactionTable">
        <thead>
            <tr>
                <th>Transaction Hash</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Amount (BTC)</th>
                <th>Timestamp</th>
                <th>Copy Hash</th>
            </tr>
        </thead>
        <tbody id="transactionData">
            <!-- Transaction data will be dynamically added here -->
        </tbody>
    </table>

    <br><br><br>
</body>

<script>
    // Function to copy text to clipboard
    function copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
</script>

<script>
    const xValues = Array.from({
        length: <?php echo count($chartData); ?>
    }, (_, i) => i + 1);
    const yValues = <?php echo json_encode($chartData); ?>;

    new Chart("myChart", {
        type: "line",
        data: {
            labels: xValues,
            datasets: [{
                data: yValues,
                borderColor: "red",
                fill: false
            }]
        },
        options: {
            legend: {
                display: false
            }
        }
    });
</script>

<script>
    // Fetch cryptocurrency data from the API
    fetch('https://api.blockchain.com/v3/exchange/tickers')
        .then(response => response.json())
        .then(data => {

            data.sort((a, b) => a.symbol.localeCompare(b.symbol));

            // Iterate over each cryptocurrency object
            data.forEach(crypto => {
                // Extract relevant information
                const {
                    symbol,
                    last_trade_price,
                    volume_24h,
                    price_24h
                } = crypto;

                // Create a new row in the table
                const newRow = document.createElement('tr');

                // Add name column
                const nameColumn = document.createElement('td');
                nameColumn.textContent = symbol;
                newRow.appendChild(nameColumn);

                // Add price column
                const priceColumn = document.createElement('td');
                priceColumn.textContent = last_trade_price;
                newRow.appendChild(priceColumn);

                // Add volume column
                const volumeColumn = document.createElement('td');
                volumeColumn.textContent = volume_24h;
                newRow.appendChild(volumeColumn);

                // Add price(24 hr) value column
                const dayPriceValue = document.createElement('td');
                dayPriceValue.textContent = price_24h;
                newRow.appendChild(dayPriceValue);

                // Add the new row to the table
                const cryptoData = document.getElementById('cryptoData');
                cryptoData.appendChild(newRow);
            });
        })
        .catch(error => {
            console.log('Error fetching cryptocurrency data:', error);
        });


    // Fetch block information from the API
    const blockHash = "0000000000000bae09a7a393a8acded75aa67e46cb81f7acaa5ad94f9eacd103";
    const blockUrl = `https://blockchain.info/rawblock/${blockHash}`;
    fetch(blockUrl)
        .then(response => response.json())
        .then(data => {
            const blockData = document.getElementById('blockData');

            // Define the block properties and their labels
            const blockProperties = [{
                    name: 'Block Hash',
                    key: 'hash'
                },
                {
                    name: 'Block Size',
                    key: 'size'
                },
                {
                    name: 'Block Height',
                    key: 'height'
                },
                {
                    name: 'Block Version',
                    key: 'ver'
                },
                {
                    name: 'Timestamp',
                    key: 'time'
                },
                {
                    name: 'Previous Block Hash',
                    key: 'prev_block'
                },
                {
                    name: 'Merkle Root',
                    key: 'mrkl_root'
                }
            ];

            // Iterate over each block property
            blockProperties.forEach(property => {
                // Create a new row in the block table
                const newRow = document.createElement('tr');

                // Add property column
                const propertyColumn = document.createElement('td');
                propertyColumn.textContent = property.name;
                newRow.appendChild(propertyColumn);

                // Add value column
                const valueColumn = document.createElement('td');
                valueColumn.textContent = data[property.key];
                newRow.appendChild(valueColumn);

                // Add the new row to the block table
                blockData.appendChild(newRow);
            });

            // Check if transactions exist
            if (data.tx && data.tx.length > 0) {
                // Retrieve the first 10 transactions
                const transactions = data.tx.slice(0, 10);

                // Iterate over each transaction
                transactions.forEach(transaction => {
                    // Create a new row in the transaction table
                    const newRow = document.createElement('tr');

                    // Add transaction hash column
                    const hashColumn = document.createElement('td');
                    const shortHash = transaction.hash.substring(0, 10) + '...';
                    hashColumn.textContent = shortHash;
                    newRow.appendChild(hashColumn);

                    // Add transaction sender column
                    const senderColumn = document.createElement('td');
                    senderColumn.textContent = transaction.inputs[0].prev_out.addr;
                    newRow.appendChild(senderColumn);

                    // Add transaction receiver column
                    const receiverColumn = document.createElement('td');
                    receiverColumn.textContent = transaction.out[0].addr;
                    newRow.appendChild(receiverColumn);

                    // Add transaction amount column
                    const amountColumn = document.createElement('td');
                    const amount = transaction.out[0].value / 100000000; // Convert satoshis to BTC
                    amountColumn.textContent = amount.toFixed(8); // Display up to 8 decimal places
                    newRow.appendChild(amountColumn);

                    // Add transaction timestamp column
                    const timestampColumn = document.createElement('td');
                    const timestamp = new Date(transaction.time * 1000);
                    timestampColumn.textContent = timestamp.toLocaleString();
                    newRow.appendChild(timestampColumn);

                    // Add copy button column
                    const copyButtonColumn = document.createElement('td');
                    const copyButton = document.createElement('button');
                    copyButton.textContent = 'Copy';
                    copyButton.addEventListener('click', () => {
                        copyToClipboard(transaction.hash);
                    });
                    copyButtonColumn.appendChild(copyButton);
                    newRow.appendChild(copyButtonColumn);

                    // Add the new row to the transaction table
                    transactionData.appendChild(newRow);
                });

                // Rename table headings based on row data
                const transactionTable = document.getElementById('transactionTable');
                const headings = transactionTable.querySelectorAll('th');

                headings[0].textContent = 'Transaction Hash';
                headings[1].textContent = 'Sender';
                headings[2].textContent = 'Receiver';
                headings[3].textContent = 'Amount (BTC)';
                headings[4].textContent = 'Timestamp';
                headings[5].textContent = 'Copy Hash';
            } else {
                console.log('No transactions found in the block.');
            }
        })
        .catch(error => {
            console.log('Error fetching block information:', error);
        });
</script>

</html>
