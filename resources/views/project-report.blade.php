<?php

$company = $item->company;
$logo_link = url('storage/' . $company->logo);
// $link = url('css/bootstrap-print.css');
use App\Models\Utils;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @include('css')
    <title>{{ $title }}</title>
</head>

<body>

    @include('widgets.header', ['logo_link' => $logo_link, 'company' => $company])
    <p class="text-center fw-900 fs-30 mt-4 pt-2 pb-2 text-uppercase" style="background-color: {{ $company->color }}; color: white;}}">
        Project Status Report
    </p>

    <p class="text-right mt-3 fw-600 text-uppercase">As On {{ Utils::my_date(now()) }}</p>


    <p class="fs-18 fw-500 mt-2 text-uppercase "><span>Project Information</span></p>
    <hr style="border-width: 4px; color: {{ $company->color }}; border-color: {{ $company->color }};" class="mt-0 mb-1">
    <table class="w-100">
        <tbody>
            <tr>
                <td>
                    @include('title-detail', ['t' => 'PROJECT NAME', 'd' => $item->name, 'style' => '2'])
                    @include('title-detail', ['t' => 'CLIENT', 'd' => $item->client->name, 'style' => '2'])
                    @include('title-detail', [
                    't' => 'CONTACT',
                    'd' => $item->client->email,
                    'style' => '2',
                    ])
                </td>
                <td>
                    @include('title-detail', [
                    't' => 'PROJECT MANAGER',
                    'd' => $item->manager->name,
                    'style' => '2',
                    ])
                    @include('title-detail', [
                    't' => 'START DATE',
                    'd' => Utils::my_date($item->created_at),
                    'style' => '2',
                    ])
                    @include('title-detail', [
                    't' => 'PLANNED END DATE',
                    'd' => Utils::my_date($item->created_at),
                    'style' => '2',
                    ])
                </td>
            </tr>
        </tbody>
    </table>


    <p class="fs-18 fw-500 mt-3 text-uppercase "><span>Project Status Summary</span></p>
    <hr style="border-width: 4px; color: {{ $company->color }}; border-color: {{ $company->color }};" class="mt-0 mb-2"> 
    <table class="w-100 ">
        <tbody>
            <tr class="text-center">
                <td style="width: 40%">
                    <p class="fs-20 text-uppercase fw-400 mt-3 "><span>WORK COMPLETION</span></p>
                    <p class="fw-900  mt-2 mb-2 text-center" style="font-size: 60px;">{{ $item->progress }}%</p>
                </td>
                <td style="border-left: 1px solid black;">
                    <h2 class="mb-2">PROJECT FINANCES</h2>
                    <table class="w-100 table ml-3 mr-3">
                        <tr>
                            <td style="width: 50%">
                                <p class="fs-30 text-uppercase fw-400 mt-0 ml-1"><span>Budget</span></p> 
                            </td>
                            <td style="width: 50%">
                                <p class="fw-900 fs-30 mt-2 mb-2 text-right">
                                    ${{ number_format($item->getBudget()) }}</p>
                            </td> 
                        </tr>
                        <tr>
                            <td style="width: 50%">
                                <p class="fs-30 text-uppercase fw-400 mt-0 ml-1 "><span>Spent</span></p> 
                            </td>
                            <td style="width: 50% text-right"> 
                                <p class="fw-900 fs-30 mt-2 mb-2 text-right">
                                    ${{ number_format($item->getExpenditure()) }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p class="fs-30 text-uppercase fw-400 mt-0 ml-1 "><span>Balance</span></p>
                            </td>
                                {{-- BALANCE --}}
                                @php
                                    $balance = $item->getBalance();
                                    $bg_class = $balance < 0 ? 'bg-danger' : ($balance > 0 ? 'bg-success' : '');
                                @endphp
                                <td style="width: 50%   " class="{{ $bg_class }}"> 
                                    <p class="fw-900 fs-30 mt-2 mb-2 text-right text-white">
                                        ${{ number_format( $balance ) }}</p>
                                </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <hr class="mt-3 bg-dark mb-2">

    <style>
        .table {
            border-collapse: collapse;
            width: 100%;
        }

        .table td,
        .table th {
            border: 1px solid black;
            padding: 2px;
        }

        .table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .table tr:hover {
            background-color: #ddd;
        }

        .table th {
            padding-top: 1px;
            padding-left: 1px;
            padding-bottom: 1px;
            text-align: left;
            background-color: #dcedf4;
            color: black;
        }
    </style> 

    <p class="fs-18 fw-500 mt-3  mt-2 mb-1"><span>Ongoing Project Activities</span></p> 
    <table class="w-100 ">
        <thead>
            <tr  style="background-color: {{ $company->color }}!important;" class="text-white">
                <th class="border-bottom border-top border-left border-right p-1">Sn.</th>
                <th class="border-bottom border-top border-left border-right p-1">Deliverable</th>
                <th class="border-bottom border-top border-left border-right p-1">Budget</th>
                <th class="border-bottom border-top border-left border-right p-1">Spent</th>
                <th class="border-bottom border-top border-left border-right p-1">Balance</th>
                <th class="border-bottom border-top border-left border-right p-1">Progress</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; ?>
            @foreach ($item->project_sections as $sec)
            <?php
            if ($sec->progress > 99) {
                continue;
            }
            $i++; ?>
            <tr>
                <td class="border-bottom border-left border-right p-1" style="width: 40px">{{ $i }}.
                </td>
                <td class="border-bottom border-left border-right p-1 w-80">
                    {{ $sec->name }}
                </td>
                <td class="border-bottom border-left border-right p-1 text-right" >
                    <b>${{ number_format($sec->getBudget()) }}</b>
                </td>
                <td class="border-bottom border-left border-right p-1 text-right"  >
                    <b>${{ number_format($sec->getExpenditure()) }}</b>
                </td>
                <td class="border-bottom border-left border-right p-1 text-right"  >
                    @php
                        $bud = $sec->getBudget();
                        $exp = $sec->getExpenditure();
                        $bal = $bud + $exp;
                        $bg_class = $bal < 0 ? 'text-danger' : ($bal > 0 ? 'text-dark' : '');
                    @endphp
                    <b class="{{ $bg_class }}">${{ number_format($bal) }}</b>
                </td>
                <td class="border-bottom border-left border-right p-1 text-right"  >
                    <b>{{ $sec->progress }}%</b>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="fs-18 fw-500 mt-4 text-uppercase "><span>Project's Health</span></p>
    <hr class="mt-1 bg-dark mb-2">
    <table class="w-100" style="vertical-align: top;">
        <tbody>
            <tr>
                <td>

                    @include('title-detail', [
                    't' => 'Budget Overview',
                    'd' => $item->budget_overview,
                    'style' => '2',
                    ])
                    @include('title-detail', [
                    't' => 'Schedule Overview',
                    'd' => $item->schedule_overview,
                    'style' => '2',
                    ])
                </td>
                <td>

                    @include('title-detail', [
                    't' => 'Project Risks & Issues',
                    'd' => $item->risks_issues,
                    'style' => '2',
                    ])
                    @include('title-detail', [
                    't' => 'Concerns/Recommendations',
                    'd' => $item->concerns_recommendations,
                    'style' => '2',
                    ])
                </td>
            </tr>
        </tbody>
    </table>
    <hr class="bg-dark">
    <p class="text-center fw-600 mb-0">This report is generated by {{ $item->manager->name }} on
        {{ Utils::my_date(now()) }}
    </p> <br>
    <p class="text-center fw-600"><small>Powered by <a href="https://8technologies.net">{{ env('APP_NAME') }} - Eight
                Tech
                Consults Ltd</a></small></p>

</body>

</html>