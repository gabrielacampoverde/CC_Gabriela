{$j = 0}
{foreach from = $saDatos item = i}
 <tr>
 <th scope="row">{$j + 1}</th>
 <td>{$i['CCODROL']}</td> 
 <td class="text-left">{$i['CDESCRI']}</td>
 <td class="text-left">{$i['CDESCOR']}</td>
 <td>
     {if $i['CESTADO'] eq 'A'}
        <img src="css/svg/ic_active_24.svg">
     {elseif $i['CESTADO'] eq 'I'}
        <img src="css/svg/ic_inactive_24.svg">
     {/if}
 </td>
 <td><input id="p_nIndice" name="p_nIndice" type="radio" value="{$j}"></td> 
 </tr>
 {$j = $j + 1}
 {/foreach}