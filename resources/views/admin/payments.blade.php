@extends('admin.layouts.default')
@section('content')

    <div class="main-central-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th></th>
                <th></th>
                <th>
                    <span>Пользователь</span>
                    <div style="text-align: center;">
                        <a class="table-direction" href="#" data-direct="up">&#9650;</a>
                        <a class="table-direction active" href="#" data-direct="down">&#9660;</a>
                    </div>
                </th>
                <th>
                    <span>Создан</span>
                    <div style="text-align: center;">
                        <a class="table-direction" href="#" data-direct="up">&#9650;</a>
                        <a class="table-direction" href="#" data-direct="down">&#9660;</a>
                    </div>
                </th>
                <th>
                    <span>Оплачен</span>
                    <div style="text-align: center;">
                        <a class="table-direction" href="#" data-direct="up">&#9650;</a>
                        <a class="table-direction" href="#" data-direct="down">&#9660;</a>
                    </div>
                </th>
            </tr>
            </thead>
        </table>
    </div>
    @stop