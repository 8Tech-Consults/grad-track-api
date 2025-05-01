<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\Account;
use App\Models\Enterprise;
use App\Models\Manifest;
use App\Models\MenuItem;
use App\Models\ReportCard;
use App\Models\ReportFinanceModel;
use App\Models\StockBatch;
use App\Models\StudentHasClass;
use App\Models\StudentHasTheologyClass;
use App\Models\StudentReportCard;
use App\Models\Subject;
use App\Models\TermlyReportCard;
use App\Models\TheologyClass;
use App\Models\TheologyMark;
use App\Models\TheologyTermlyReportCard;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utils;
use Carbon\Carbon;
use Dflydev\DotAccessData\Util;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialRecord;
use PDO;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        Admin::style('.content-header {display: none;}');
        $u = Admin::user();
        return $content->view('admin.index', [
            'u' => $u
        ]);
    }


    public function calendar(Content $content)
    {
        $u = Auth::user();
        $content
            ->title('Company Calendar');
        $content->row(function (Row $row) {
            $row->column(8, function (Column $column) {
                $column->append(view('dashboard.calender', [
                    'events' => []
                ]));
            });
            $row->column(4, function (Column $column) {
                $u = Admin::user();
                $column->append(view('dashboard.upcoming-events', [
                    'items' => []
                ]));
            });
        });
        return $content;


        return $content;
    }


    public function stats(Content $content)
    {

        $u = Admin::user();


        //$warnings = Utils::get_system_warnings($u->ent);

        if (!empty($warnings)) {
            $content->row(function (Row $row) use ($warnings) {
                $row->column(12, function (Column $column) use ($warnings) {
                    $column->append(view('widgets.system-warnings', [
                        'warnings' => $warnings
                    ]));
                });
            });
        }
        /*       if (
            true
        ) {
            $content->row(function (Row $row) {
                $row->column(6, function (Column $column) {
                    $column->append(Dashboard::bursarServices()); 
                });
            }); 
        } */

        if (
            $u->isRole('admin') ||
            $u->isRole('hm') ||
            $u->isRole('bursar')
        ) {

            $content->row(function (Row $row) {

                $row->column(3, function (Column $column) {
                    $column->append(Dashboard::students());
                });

                $row->column(3, function (Column $column) {
                    $column->append(Dashboard::teachers());
                });

                $row->column(3, function (Column $column) {
                    $column->append(Dashboard::count_expected_fees());
                });
             

     
                $row->column(3, function (Column $column) {
                    /* $total_expected = (Manifest::get_total_expected_tuition(Auth::user()) + Manifest::get_total_expected_tuition(Auth::user()));
                    $total_balance = Manifest::get_total_fees_balance(Auth::user());
                    $paid = $total_expected + $total_balance; */
                    $u = Admin::user();
                    $ent = $u->ent;
                    $term = $ent->active_term();
                  
                    $transsactions_tot = Transaction::where([
                        'enterprise_id' => $ent->id,
                        'term_id' => $term->id,
                    ])
                        ->where('amount', '>', 0)
                        ->sum('amount');

                    $column->append(view('widgets.box-5', [
                        'is_dark' => true,
                        'title' => 'EXPENDITURE',
                        'sub_title' => 'Total Expenditure',
                        'number' => "<small class=\"text-white\">$</small>" . number_format(FinancialRecord::where([
                                'type' => 'EXPENDITURE',
                            ])->sum('amount')),
                        'link' => admin_url('transactions')
                    ]));
                });

  
            });


            $content->row(function (Row $row) {
                $row->column(3, function (Column $column) {
                    $column->append(Dashboard::recent_fees_payment());
                });
                $row->column(6, function (Column $column) {
                    $column->append(Dashboard::fees_collection());
                });
                $row->column(3, function (Column $column) {
                    $column->append(Dashboard::recent_fees_bills());
                });
            });


            $content->row(function (Row $row) {
                $row->column(6, function (Column $column) {
                    $column->append(Dashboard::expenditure());
                });
                $row->column(6, function (Column $column) {
                    $column->append(Dashboard::budget());
                });
            });
        }

        /* 
        if (
            $u->isRole('bursar')
        ) {


            $content->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $column->append(Dashboard::bursarFeesServices());
                });
            });

            $content->row(function (Row $row) {
                $row->column(6, function (Column $column) {
                    $column->append(Dashboard::bursarFeesExpected());
                });
                $row->column(6, function (Column $column) {
                    $column->append(Dashboard::bursarFeesPaid());
                });
            });
        }

 */


        /*         if ($u->isRole('teacher')) {
            $content->row(function (Row $row) {
                $row->column(3, function (Column $column) {
                    $column->append(Dashboard::teacher_marks());
                });
                $row->column(3, function (Column $column) {
                    $column->append(Dashboard::theology_teacher_marks());
                });
            });
        } */

        return $content;
    }
}
