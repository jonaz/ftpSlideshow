(function($){
	// **ListView class**: Our main app view.

	function qs(key) {
		key = key.replace(/[*+?^$.\[\]{}()|\\\/]/g, "\\$&"); // escape RegEx meta chars
		var match = location.search.match(new RegExp("[?&]"+key+"=([^&]+)(&|$)"));
		return match && decodeURIComponent(match[1].replace(/\+/g, " "));
	}

	var ImageModel = Backbone.Model.extend({
		defaults: {
			"image": 0
		},
		url: function(){
			var url = './getImage.php?';
			url += 'folder='+this.get('folder');
			url += '&count';
			return url;
		},
		initialize: function(){
			this.set('folder',qs('folder'));
			this.set('size',qs('s'));
			this.fetch();
		},
		nextImage: function(){
			var self = this;
			this.fetch({success: function(){
				var next = self.get('image');
				if(next >= self.get('count')-1){
					self.set('image',0);
				}else{
					self.set('image',next+1);
				}
			}});
		},
		getImageUrl: function(i){
			var image = i || this.get('image');
			var url = './getImage.php?';
			url += 'folder='+this.get('folder');
			url += '&s='+this.get('size');
			url += '&image='+i;
			return url;
		}

	});

	var imageModel = new ImageModel();

	var ListView = Backbone.View.extend({
		model: imageModel,
		el: $('body'),
		initialize: function(){
			var self = this;
			var interval = qs('interval');

			if(interval === null){
				interval = 10000;
			}
			this.timer = setInterval(function() {
				self.model.nextImage();
			}, interval);

			this.listenTo(this.model,'change:image',this.changeImage,this);
			this.listenTo(this.model,'change:count',this.render,this);
		},

		render: function(){
			var img;
			this.$el.find('#image img.image').remove();
			for(var i = 0;i< this.model.get('count');i++){
				img = $('<img id="image_'+i+'">');
				img.attr('src', this.model.getImageUrl(i));
				img.addClass('image');
				img.hide();
				this.$el.find('#image').append(img);
			}
		},
		changeImage: function(){
			this.$el.find('#image img.image').hide();
			this.$el.find('#image_'+this.model.get('image')).show();
			console.log('change image');
			console.log(this.model.get('image'));
		}
	});

	var listView = new ListView();
	console.log(listView.model);
})(jQuery);
