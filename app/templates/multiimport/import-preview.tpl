<section class="box" v-if="results.length > 0">
<h3>folgende Daten wurden erkannt: </h3>
<div class="data-preview">
	<div class="data-preview-item" v-for="result in results">

		<h3>{{result.name}}

			<button @click="removeImport(result.index)" v-if="result.status" class="button danger fright"><img class="trashbin" src="/styles/flundr/img/icon-delete-white.svg"> Import widerrufen</button>

			<span v-if="loaders[result.index]">wird verarbeitet</span> <div v-if="loaders[result.index]" class="loadIndicator"><div></div><div></div><div></div></div>
		</h3>

		<table v-if="result.status" class="fancy">
		<thead>
			<tr>
				<th>Vorname</th>
				<th>Nachname</th>
				<th>Ort</th>
				<th>Geburtstag</th>
				<th>Alter</th>
			</tr>
		</thead>
		<tbody>

		<tr v-for="entry in result.status">
			<td>{{entry.firstname}}</td>
			<td>{{entry.lastname}}</td>
			<td>{{entry.location}}</td>
			<td>{{entry.birthday}}</td>
			<td>{{entry.age}}</td>
		</tr>	

		</tbody>
		</table>

	</div>
<div>


</section>
