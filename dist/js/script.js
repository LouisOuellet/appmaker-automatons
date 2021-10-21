API.Plugins.automatons = {
	element:{
		table:{
			index:{},
		},
	},
	init:function(){
		API.GUI.Sidebar.Nav.add('Automatons', 'administration');
	},
	load:{
		index:function(){
			API.Builder.card($('#pagecontent'),{ title: 'Automatons', icon: 'automatons'}, function(card){
				API.request('automatons','read',{
					data:{options:{ link_to:'AutomatonsIndex',plugin:'automatons',view:'index' }},
				},function(result) {
					var dataset = JSON.parse(result);
					if(dataset.success != undefined){
						for(const [key, value] of Object.entries(dataset.output.dom)){ API.Helper.set(API.Contents,['data','dom','automatons',value.id],value); }
						for(const [key, value] of Object.entries(dataset.output.raw)){ API.Helper.set(API.Contents,['data','raw','automatons',value.id],value); }
						API.Builder.table(card.children('.card-body'), dataset.output.dom, {
							headers:dataset.output.headers,
							id:'AutomatonsIndex',
							modal:true,
							key:'name',
							clickable:{ enable:true, view:'details'},
							controls:{ toolbar:true},
							import:{ key:'id', },
						},function(response){
							API.Plugins.automatons.element.table.index = response.table;
						});
					}
				});
			});
		},
	},
	extend:{},
}

API.Plugins.automatons.init();
