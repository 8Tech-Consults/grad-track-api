<?php

namespace App\Admin\Controllers;

use App\Models\Account;
use App\Models\AccountParent;
use App\Models\FinancialRecord;
use App\Models\Term;
use App\Models\Utils;
use Dflydev\DotAccessData\Util;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinancialBudgetRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Budget';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FinancialRecord());




        /*     
    "enterprise_id" => 7
    "account_id" => 865
    "academic_year_id" => 2
    "term_id" => 6
    "parent_account_id" => 1
    "created_by_id" => 2985
    "amount" => -350000
    "termly_school_fees_balancing_id" => null
    "description" => "Cartridge refill"
    "type" => "EXPENDITURE"
    "payment_date" => "2022-10-19"
    "quantity" => 1
    "unit_price" => 1 */


        $grid->export(function ($export) {
            $export->filename('Financial Records');
            $export->except(['actions']);
            $export->originalValue(['description', 'type']);
        });





        $grid->filter(function ($filter) {
            // Remove the default id filter
            $filter->disableIdFilter();
            $u = Admin::user();
            $accs = [];
            foreach (
                Account::where([])
                    ->get() as $val
            ) {
                if ($val->account_parent_id == null) {
                    continue;
                }

                $accs[$val->id] = $val->getName();
            }
            $parents = [];



            foreach (
                AccountParent::where([
                    'enterprise_id' => $u->enterprise_id,
                ])
                    ->orderBy('id', 'desc')
                    ->get() as $v
            ) {
                $parents[$v->id] = $v->name;
            }


            $filter->equal('parent_account_id', 'Filter by project')
                ->select($parents);

            $filter->equal('account_id', 'Filter by activity')
                ->select($accs);




            $filter->between('payment_date', 'Date Created between')->date();

            $filter->group('amount', function ($group) {
                $group->gt('greater than');
                $group->lt('less than');
                $group->equal('equal to');
            });
        });

        $grid->quickSearch('description');


        $grid->model()->where([
            'type' => 'BUDGET',
        ])->orderBy('id', 'asc');

        $grid->column('created_at', __('Created'))
            ->display(function ($x) {
                return Utils::my_date($x);
            })
            ->sortable()
            ->hide();

        $grid->column('payment_date', __('Created'))
            ->display(function ($x) {
                return Utils::my_date($x);
            })->sortable();

        $grid->column('description', __('Particulars'))
            ->display(function ($x) {
                return '<spap title="' . $x . '" >' . Str::limit($x, 40, '...') . '</span>';
            })->sortable();
        $grid->column('detail', __('Details'))->hide();
        $grid->column('quantity', __('Quantity'))
            ->display(function ($x) {
                return  number_format($x);
            });
        $grid->column('unit_price', __('Unit price ($)'))
            ->display(function ($x) {
                return '$' . number_format($x);
            });
        $grid->column('amount', __('Total ($)'))
            ->display(function ($x) {
                return '$' . number_format($x);
            });
        $grid->column('type', __('Type'))
            ->dot([
                'EXPENDITURE' => 'danger',
                'BUDGET' => 'success',
            ])
            ->filter([
                'BUDGET' => 'BUDGET',
                'EXPENDITURE' => 'EXPENDITURE',
            ])->sortable()
            ->hide();


        $grid->column('account_id', __('Activity'))
            ->display(function ($x) {
                if ($this->account == null) {
                    return $x;
                }
                return $this->account->name;
            })->sortable();


        $grid->column('parent_account_id', __('Project'))
            ->display(function ($x) {
                if ($this->par == null) {
                    return $x;
                }
                return $this->par->short_name;
            })->sortable();


        $grid->column('created_by_id', __('Created by'))
            ->display(function ($x) {
                if ($this->created_by == null) {
                    return $x;
                }
                return $this->created_by->name;
            })->sortable();



        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(FinancialRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('enterprise_id', __('Enterprise id'));
        $show->field('account_id', __('Account id'));
        $show->field('academic_year_id', __('Academic year id'));
        $show->field('term_id', __('Term id'));
        $show->field('parent_account_id', __('Parent account id'));
        $show->field('created_by_id', __('Created by id'));
        $show->field('amount', __('Amount'));
        $show->field('termly_school_fees_balancing_id', __('Termly school fees balancing id'));
        $show->field('description', __('Description'));
        $show->field('type', __('Type'));
        $show->field('payment_date', __('Payment date'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new FinancialRecord());
        $u = Auth::user();
        $form->hidden('enterprise_id', __('Enterprise id'))->default($u->enterprise_id)->rules('required');
        if ($form->isCreating()) {
            $form->hidden('created_by_id', __('Enterprise id'))->default($u->id)->rules('required');
        }

        $form->text('type', __('Record Type'))
            ->readonly()
            ->value('BUDGET')
            ->rules('required');

        $term = $u->ent->active_term();
        $form->hidden('academic_year_id', __('Academic year id'))->default(1);
        $form->hidden('term_id', __('Academic year id'))->default(1);
        $form->date('payment_date', __('Due Date'))->default(date('Y-m-d'))->rules('required');
        $form->divider();

        $ajax_url = url(
            '/api/ajax?'
                . 'enterprise_id=' . $u->enterprise_id
                . "&search_by_1=name"
                . "&search_by_2=id"
                . "&model=Account"
        );
        $ajax_url = trim($ajax_url);

        $accs = [];
        foreach (
            Account::where([])
                ->get() as $val
        ) {

            $accs[$val->id] = $val->getName();
        }

        $account_id = null;
        if (isset($_GET['account_id'])) {
            $account_id = $_GET['account_id'];
        }


        $form->select('account_id', "Activity")
            ->default($account_id)
            ->options($accs)->rules('required');


        //$form->number('academic_year_id', __('Academic year id'));
        //$form->number('term_id', __('Term id'));
        //$form->number('parent_account_id', __('Parent account id'));
        //$form->number('created_by_id', __('Created by id'));
        //$form->number('termly_school_fees_balancing_id', __('Termly school fees balancing id'));


        $form->text('description', __('Particulars'))->required()
            ->rules('required');
        $form->textarea('detail', __('Details'));
        $form->decimal('quantity', __('Quantity'))
            ->rules('required');
        $form->decimal('unit_price', __('Unit price'))->rules('required');


        $form->disableViewCheck();
        $form->disableReset();


        return $form;
    }
}
