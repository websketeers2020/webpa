@extends('layout')
@section('title')
Comments
@endsection
@section('content')
<table class="table is-stripped is-hoverable">  
    <thead>    
        <th>User</th>    
        <th>Comment</th>    
        <th>Date</th>  
        </thead>  
        <tbody>    
        @foreach ($comments as $c)      
        <tr>        
        <td>{{ $c -> name }}</td>        
        <td>{{ $c -> comment }}</td>        
        <td>b</td>      
        </tr>    
        @endforeach  
        </tbody>
</table>
{{ $comments -> links () }}
@endsection