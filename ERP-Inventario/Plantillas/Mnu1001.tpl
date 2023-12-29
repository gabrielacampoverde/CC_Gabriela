{$lcCodRol = '*'}    
{foreach $saDatos as $i}  
    {if $lcCodRol neq $i['CCODROL']}
        <div class="col-12">
            <div class="page-header">
                <h2 class="main-content-title tx-2 mg-b-2">{$i['CDESROL']}</h2>
            </div>
        </div>
    {/if}
    <div class="col-xs-12 col-sm-6 col-md-4 col-xl-3 col-lg-6">
        <div class="card custom-card text-center">   
            <div class="card-body dash1 text-center">
                <div><a href="{$i['CCODOPC']}.php"><img src="img/MenuImagen/{$i['CIMAGE']}" width="80" height="80"></a></div>
                <br>
                <div><h4><a href="{$i['CCODOPC']}.php">{$i['CDESOPC']}</a></h4></div>
                <div class="progress mb-1">
                    <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                        class="progress-bar progress-bar-xs wd-100p bg-success"
                        role="progressbar"></div>
                </div>
            </div>
        </div>
    </div>
{$lcCodRol = $i['CCODROL']}
{/foreach} 