<?php

namespace App\Admin\Controllers;

use App\Models\Client;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ClientController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Partners';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {


        $academicPartners = [
            [
                'name'        => 'National Agricultural Research Organisation',
                'short_name'  => 'NARO',
                'description' => 'Uganda’s premier national agricultural research body focusing on crop, livestock, and fisheries research to enhance food security.',
            ],
            [
                'name'        => 'International Institute of Tropical Agriculture',
                'short_name'  => 'IITA',
                'description' => 'CGIAR centre dedicated to improving crop productivity and reducing poverty through tropical agriculture research and innovation.',
            ],
            [
                'name'        => 'International Livestock Research Institute',
                'short_name'  => 'ILRI',
                'description' => 'Research organisation applying science and partnership to improve food and nutritional security and reduce poverty in developing countries.',
            ],
            [
                'name'        => 'AfricaRice',
                'short_name'  => 'AfricaRice',
                'description' => 'Pan-African rice research organisation working to develop sustainable rice production systems and improve farmer livelihoods.',
            ],
            [
                'name'        => 'International Potato Center',
                'short_name'  => 'CIP',
                'description' => 'Global research-for-development institution specializing in potato and sweetpotato solutions for food security, nutrition, and income.',
            ],
            [
                'name'        => 'International Maize and Wheat Improvement Center',
                'short_name'  => 'CIMMYT',
                'description' => 'CGIAR centre leading global efforts on maize and wheat science to increase productivity, resilience, and climate adaptation.',
            ],
            [
                'name'        => 'International Center for Tropical Agriculture',
                'short_name'  => 'CIAT',
                'description' => 'Research centre focusing on tropical agriculture, natural resource management, and climate-smart food systems.',
            ],
            [
                'name'        => 'International Water Management Institute',
                'short_name'  => 'IWMI',
                'description' => 'Research-for-development organisation providing solutions for sustainable use of water and land resources in agriculture.',
            ],
            [
                'name'        => 'Biosciences eastern and central Africa – ILRI Hub',
                'short_name'  => 'BecA-ILRI Hub',
                'description' => 'Pan-African laboratory for advanced biosciences research, capacity building, and technology transfer for agricultural development.',
            ],
            [
                'name'        => 'Kenya Agricultural & Livestock Research Organization',
                'short_name'  => 'KALRO',
                'description' => 'Kenyan state corporation conducting research to boost agricultural productivity, animal health, and crop breeding in East Africa.',
            ],
            [
                'name'        => 'World Agroforestry Centre',
                'short_name'  => 'ICRAF',
                'description' => 'International organisation advancing agroforestry science and practice for sustainable landscapes and improved livelihoods.',
            ],
            [
                'name'        => 'International Food Policy Research Institute',
                'short_name'  => 'IFPRI',
                'description' => 'Global research institute generating evidence to improve food policies, reduce poverty, and end hunger and malnutrition.',
            ],
            [
                'name'        => 'WorldFish',
                'short_name'  => 'WorldFish',
                'description' => 'Research organisation developing innovations in aquaculture and fisheries to improve nutrition, livelihoods, and ecosystem health.',
            ],
            [
                'name'        => 'Bioversity International',
                'short_name'  => 'Bioversity',
                'description' => 'Research centre focused on agricultural biodiversity to sustainably transform food systems and enhance nutrition and resilience.',
            ],
            [
                'name'        => 'International Center for Agricultural Research in the Dry Areas',
                'short_name'  => 'ICARDA',
                'description' => 'CGIAR centre addressing food security and livelihoods in dry areas through crop improvement and sustainable agri-practices.',
            ],
            [
                'name'        => 'Tanzania Agricultural Research Institute',
                'short_name'  => 'TARI',
                'description' => 'National institute conducting research on crops, livestock, and agro-technologies to support Tanzania’s agricultural development.',
            ],
            [
                'name'        => 'Sokoine University of Agriculture',
                'short_name'  => 'SUA',
                'description' => 'Tanzania-based university offering teaching and research in agriculture, forestry, veterinary, and food sciences.',
            ],
            [
                'name'        => 'Makerere University',
                'short_name'  => 'Makerere',
                'description' => 'Uganda’s oldest and largest university with strong programmes in agricultural sciences and rural development.',
            ],
            [
                'name'        => 'Egerton University',
                'short_name'  => 'Egerton',
                'description' => 'Kenyan institution renowned for its faculty of agriculture and strong engagement in agribusiness research and outreach.',
            ],
            [
                'name'        => 'University of Nairobi',
                'short_name'  => 'UoN',
                'description' => 'Leading Kenyan university with a robust College of Agriculture and Veterinary Sciences undertaking cutting-edge research.',
            ],
        ];
        $faker = \Faker\Factory::create();


        $grid = new Grid(new Client());
        $grid->model()->orderBy('name', 'Asc');
        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by name');
        $grid->column('logo', __('Logo'))
            ->lightbox(['width' => 60, 'height' => 60])
            ->sortable();
        $grid->column('name', __('Company Name'))->sortable();
        $grid->column('short_name', __('Short name'))->hide();
        $grid->column('phone_number', __('Phone number'))->sortable();
        $grid->column('phone_number_2', __('Phone number 2'))->hide();
        $grid->column('p_o_box', __('P o box'))->hide();
        $grid->column('email', __('Email'))->sortable();
        $grid->column('website', __('Website'))->hide();
        $grid->column('address', __('Address'));
        $grid->column('details', __('Details'))->hide();
        $grid->column('created_at', __('Joined'))->sortable()
            ->display(function ($created_at) {
                return date('d-m-Y', strtotime($created_at));
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
        $show = new Show(Client::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('company_id', __('Company id'));
        $show->field('name', __('Name'));
        $show->field('short_name', __('Short name'));
        $show->field('logo', __('Logo'));
        $show->field('color', __('Color'));
        $show->field('phone_number', __('Phone number'));
        $show->field('phone_number_2', __('Phone number 2'));
        $show->field('p_o_box', __('P o box'));
        $show->field('email', __('Email'));
        $show->field('website', __('Website'));
        $show->field('address', __('Address'));
        $show->field('details', __('Details'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Client());
        $form->hidden('company_id')->value(auth()->user()->company_id);
        $form->text('name', __('Client Name'))->rules('required');
        $form->text('short_name', __('Client Short Name'))->rules('required');
        $form->image('logo', __('Client Logo'));
        $form->color('color', __('Color'));
        $form->text('phone_number', __('Phone number'));
        $form->text('phone_number_2', __('Phone number 2'));
        $form->textarea('p_o_box', __('P o box'));
        $form->text('email', __('Email'))->rules('required');
        $form->text('website', __('Website'));
        $form->text('address', __('Address'));
        $form->quill('details', __('Details'));

        return $form;
    }
}
