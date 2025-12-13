<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .w-full { width: 100%; }
        .w-half { width: 50%; }
        .margin-top { margin-top: 1.25rem; }

        .header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
        }

        .company-details {
            margin-bottom: 30px;
        }

        .billing-details {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
        }

        .table {
            width: 100%;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        .table th {
            background-color: rgb(96 165 250);
            color: white;
            padding: 12px;
            text-align: left;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            background-color: rgb(241 245 249);
        }

        .total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            padding: 20px;
            background-color: rgb(241 245 249);
            font-size: 0.875rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <table class="w-full">
                <tr>
                    <td class="w-half">
                        <h1>INVOICE</h1>
                        <div>Invoice #{{ $order->id }}</div>
                        <div>Date: {{ $order->created_at->format('d/m/Y') }}</div>
                    </td>
                    <td class="w-half" style="text-align: right;">
                        <h2>Kiosk Ummah Sdn. Bhd.</h2>
                        <div>123 Business Street</div>
                        <div>Kuantan, Pahang, ZIP</div>
                        <div>Phone: (+60) 11-37073948</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="margin-top">
            <table class="w-full">
                <tr>
                    <td class="w-half">
                        <div><h4>Bill To:</h4></div>
                        <div>{{ $order->user->name ?? 'Customer' }}</div>
                        @if($order->user_address)
                            <div>{{ $order->user_address->address1 }}</div>
                            <div>{{ $order->user_address->city }}, {{ $order->user_address->state }}</div>
                            <div>{{ $order->user_address->zipcode }}</div>
                        @endif
                    </td>
                    <td class="w-half">
                        <div><h4>Payment Details:</h4></div>
                        <div>Status: {{ ucfirst($order->status) }}</div>
                        <div>Payment Method: Stripe</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="margin-top">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->order_items as $item)
                    <tr>
                        <td>{{ $item->product->title }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>RM {{ number_format($item->unit_price, 2) }}</td>
                        <td>RM {{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="total">
            Total Amount: RM {{ number_format($order->total_price, 2) }}
        </div>

        <div class="footer">
            <div>Thank you for your business!</div>
            <div>Terms & Conditions Apply</div>
            <div>&copy; {{ date('Y') }} Kiosk Ummah. All rights reserved.</div>
        </div>
    </div>
</body>
</html>
