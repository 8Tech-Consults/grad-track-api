<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Post\BatchSetNotProcessedAccountController;
use App\Admin\Actions\Post\BatchSetProcessedAccountController;
use App\Models\Account;
use App\Models\AccountParent;
use App\Models\Enterprise;
use App\Models\Utils;
use Dflydev\DotAccessData\Util;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class AccountController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Project Activities';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {


        
        $grid = new Grid(new Account());
        $grid->disableBatchActions();

        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
            $batch->add(new BatchSetProcessedAccountController());
        });



        $grid->model()
            ->orderBy('id', 'Asc');



        $grid->filter(function ($filter) {
            // Remove the default id filter
            $filter->disableIdFilter();



            $filter->equal('account_parent_id', 'Filter by Project')
                ->select(
                    AccountParent::where([
                        'enterprise_id' => Admin::user()->enterprise_id,
                    ])->orderBy('name', 'Asc')->get()->pluck('name', 'id')
                );


            $filter->group('balance', function ($group) {
                $group->gt('greater than');
                $group->lt('less than');
                $group->equal('equal to');
            });
        });


        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });



        $ent = Enterprise::find(Admin::user()->enterprise_id);
        $grid->model()->where([
            'enterprise_id' => Admin::user()->enterprise_id,
            'administrator_id' => $ent->administrator_id,
        ])
            ->orderBy('id', 'Asc');

        $grid->quickSearch('name')->placeholder('Search by account name');
        $grid->column('id', __('#ID'))
            ->sortable();

        $grid->column('owner.avatar', __('Photo'))
            ->width(80)
            ->hide()
            ->lightbox(['width' => 60, 'height' => 60]);

        $grid->column('name', __('Activity'))->sortable();
        $grid->column('account_parent_id', __('Project'))
            ->display(function () {
                $acc =  Utils::getObject(AccountParent::class, $this->account_parent_id);
                if ($acc == null) {
                    return "None";
                }
                return $acc->short_name;
            })
            ->sortable();


        $grid->column('budget', __('Budget ($)'))->display(function () {
            $val =  '$' . number_format($this->getBudget());
            $text = '<a 
            target="_blank"
            title="Click to view these budgets"
            href="' . admin_url('financial-records-budget?account_id=' . $this->id) . '" class=" text-dark text-bold m-0">' . $val . '</a>';
            return  $text;
        })->sortable();

        $grid->column('expense', __('Expense'))->display(function () {
            $val =  '$' . number_format($this->getExpenditure());
            $text = '<a 
            target="_blank"
            title="Click to view these expenses"
            href="' . admin_url('financial-records-expenditure?account_id=' . $this->id) . '" class=" text-dark text-bold m-0">' . $val . '</a>';
            return  $text;
        })->sortable();


        $grid->column('balance', __('Balance'))->display(function ($bud) {
            $exp = $this->getExpenditure();
            $bud = $this->getBudget();
            $bal = $bud + $exp;
            $color = "green";
            if ($bal < 0) {
                $color = "red";
            }
            return '<span class="p-1 text-white" style="font-wight: 800!important; background-color: ' . $color . ';">UGX ' . number_format($bal) . '</span>';
        });


        //anjane

        $grid->export(function ($export) {

            $export->filename('Accounts');
            $export->except(['balance']);

            $export->except(['enterprise_id', 'type', 'owner.avatar', 'id']);
            /* $export->column('balance', function ($value, $original) {
                $term = Auth::user()->ent->dpTerm();
                $bud = $this->getBudget($term);
                $exp = $this->getExpenditure($term);
                $bal = $bud + $exp;
                return $bal;
            }); */
        });


        $grid->column('quick_actions', __('Quick Actions'))->width(200)->display(function () {
            $_add_activitiy = '<a target="_blank" href="' . admin_url('financial-records-budget/create?account_id=' . $this->id) . '" class="btn btn-xs btn-primary m-0">Allocate Funds</a>';
            $view_activities = '<a target="_blank" href="' . admin_url('financial-records-expenditure/create?account_id=' . $this->id) . '" class="btn btn-xs btn-primary m-0">Add Expense</a>';
            return $_add_activitiy . ' ' . $view_activities;
        });

        $grid->column('description', __('Description'))
            ->hide();

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
        $show = new Show(Account::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('enterprise_id', __('Enterprise id'));
        $show->field('administrator_id', __('Administrator id'));
        $show->field('name', __('Name'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Account());

        $payable = 0;
        $paid = 0;
        $balance = 0;
        $id = 0;



        $account_parent_id = null;
        if (isset($_GET['account_parent_id'])) {
            $account_parent_id = $_GET['account_parent_id'];
        }

        $u = Admin::user();
        $ent = Enterprise::find($u->enterprise_id);
        $form->hidden('enterprise_id', __('Enterprise id'))->default($u->enterprise_id)->rules('required');
        $form->hidden('administrator_id', __('Enterprise id'))->default($ent->administrator_id)->rules('required');


        $form->text('name', __('Activit Title'))
            ->rules('required');

        $form->select('account_parent_id', "Project")
            ->options(
                AccountParent::where([
                    'enterprise_id' => Admin::user()->enterprise_id
                ])->orderBy('name', 'Asc')->get()->pluck('name', 'id')
            )
            ->default($account_parent_id)
            ->rules('required');
      
        
        $form->textarea('description', __('Activity description'));


        /*
            ->when('OTHER_ACCOUNT', function ($f) {
                $u = Admin::user();
                $ajax_url = url(
                    '/api/ajax?'
                        . 'enterprise_id=' . $u->enterprise_id
                        . "&search_by_1=name"
                        . "&search_by_2=id"
                        . "&model=User"
                );
                $f->select('administrator_id', "Account owner")
                    ->options(function ($id) {
                        $a = Account::find($id);
                        if ($a) {
                            return [$a->id => "#" . $a->id . " - " . $a->name];
                        }
                    })
                    ->ajax($ajax_url)->rules('required');
            });*/


        $form->file('completion_report_pdf', __('Activity Completion Report'))
            ->rules('mimes:pdf|max:2048')
            ->help('Upload a PDF file of the completion report');

        $form->disableViewCheck();

        $form->radio('status', "Activity status")
            ->options([
                'ACTIVE' => 'Ongoing',
                'COMPLETED' => 'Completed',
                'CANCELLED' => 'Cancelled',
            ]) 
        ->when('ACTIVE', function ($f) { 
            $f->text('progress', __('Activity Progress (%)'))
            ->rules('required')
            ->default(0);
        })->when('COMPLETED', function ($f) { 
            $f->file('completion_report_pdf', __('Attach Activity Completion Report'));
        })->default('ACTIVE')
            ->rules('required'); 
 

        return $form;
    }
}
