<?php

namespace App\Admin\Controllers;

use App\Models\AccountParent;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class AccountParentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Projects';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AccountParent());



        $grid->disableBatchActions();
        $grid->model()->where('enterprise_id', Admin::user()->enterprise_id)
            ->orderBy('id', 'Asc');

        $grid->column('logo', __('Logo'))
            ->lightbox(['width' => 60, 'height' => 60])
            ->sortable();

        $grid->column('name', __('Name'))->sortable();

        $grid->column('budget', __('Budget'))->display(function () {
            $term = Auth::user()->ent->dpTerm();
            return 'UGX ' . number_format($this->getBudget($term));
        });

        $grid->column('expense', __('Expense'))->display(function () {
            $term = Auth::user()->ent->dpTerm();
            return 'UGX ' . number_format($this->getExpenditure($term));
        });

        $grid->column('balance', __('Balance'))->display(function () {
            $term = Auth::user()->ent->dpTerm();
            $bud = $this->getBudget($term);
            $exp = $this->getExpenditure($term);
            $bal = $bud + $exp;
            $color = "green";
            if ($bal < 0) {
                $color = "red";
            }
            return '<span class="p-1 text-white" style="font-wight: 800!important; background-color: ' . $color . ';">UGX ' . number_format($bal) . '</span>';
        });

        $grid->column('Accounts', __('Accounts'))->display(function () {
            return count($this->accounts);
        });
        $grid->column('description', __('Description'))->hide();
        $grid->column('status', __('Status'))->sortable()
            ->label([
                'Active' => 'primary',
                'Completed' => 'success',
                'On-hold' => 'warning',
                'Cancelled' => 'danger',
            ], 'danger');

        $grid->column('progress', __('Progress'))->sortable()
            ->progressBar($style = 'primary', $size = 'sm', $max = 100)
            ->totalRow(function ($amount) {

                $amount = round($amount, 2);
                if ($amount < 70) {
                    return "<b class='text-danger'>Total progress: $amount%</b>";
                } else {
                    return "<b class='text-success'>Total progress: $amount%</b>";
                }
            })->sortable();

        //add actions column
        $grid->column('quick_actions', __('Quick Actions'))->width(100)->display(function () {
            $_add_activitiy = '<a href="' . admin_url('accounts/create?account_parent_id=' . $this->id) . '" class="btn btn-xs btn-primary mb-1">Add Activity</a><br>';
            $view_activities = '<a href="' . admin_url('accounts?&account_parent_id=' . $this->id) . '" class="btn btn-xs btn-primary">View Activities</a>';
            return $_add_activitiy . ' ' . $view_activities;
        });

        $grid->column('print', __('Repoert'))
            ->display(function ($created_at) {
                $url = url('project-report?id=' . $this->id);
                return "<a href='{$url}' target='_blank' class='btn btn-sm btn-primary'>Generate Report</a>";
            });
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
        $show = new Show(AccountParent::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('enterprise_id', __('Enterprise id'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AccountParent());

        $u = Admin::user();
        $form->hidden('enterprise_id', __('Enterprise id'))->default($u->enterprise_id)->rules('required');
        $clients = \App\Models\Client::where([])
            ->orderBy('name')
            ->pluck('name', 'id');
        $form->text('name', __('Project Name'))->rules('required');
        $form->text('short_name', __('Project Short name'))->rules('required');
        $form->hidden('enterprise_id')->value(auth()->user()->enterprise_id);
        $form->select('client_id', __('Project Client'))
            ->options($clients)
            ->rules('required');

        $form->date('start_date', __('Project Start Date'))->rules('required');
        $form->date('end_date', __('Project End Date'))->rules('required');
        $form->multipleSelect('other_clients', "Partners")
            ->options($clients);

        $employees = Administrator::where('enterprise_id', auth()->user()->enterprise_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        $form->select('administrator_id', __('Project Manager'))
            ->options($employees)
            ->rules('required');

        $form->image('logo', __('Project Icon (Logo)'));

        $form->quill('details', __('Project Details'));

        $form->divider();
        $form->text('budget_overview', __('Budget overview'));
        $form->text('schedule_overview', __('Scheduled overview'));
        $form->text('risks_issues', __('Project Risks & Issues'));
        $form->text('concerns_recommendations', __('Concerns and Recommendations'));

        $form->radio('status', __('Project Status'))
            ->options([
                'Active' => 'Active',
                'Completed' => 'Completed',
                'On-hold' => 'On Hold',
                'Cancelled' => 'Cancelled',
            ])
            ->default('active')
            ->rules('required');


        if ($form->isCreating()) {
            $form->hidden('progress', __('Progress'))->default(0);
        }

        return $form;
    }
}
