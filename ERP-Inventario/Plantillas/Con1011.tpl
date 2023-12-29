{$k = 0}
{foreach from=$saDatos item=i}
	<tr>
		<td class="text-center" style="width: 1%" scope="row">{$k+1}</td>
		<td class="text-center" style="width: 1%" scope="col">{$i['CNRODNI']}</td>
		<td class="text-center" style="width: 1%" scope="col">{$i['CCODALU']}</td>
		<td class="text-left" scope="col">{$i['CNOMBRE']}</td>
		<td class="text-center" scope="col">{$i['CPROYEC']}</td>
		<td class="text-right" scope="col">{$i['NDEUDA']}</td>
	</tr>
  {$k = $k + 1}
{/foreach}
