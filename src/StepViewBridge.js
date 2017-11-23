window.rhubarb.vb.create('StepViewBridge', function(){
   return {
       attachEvents: function(){
           var links = this.viewNode.querySelectorAll('.js-wizard-step-link');

           for(var i in links){
               if (links.hasOwnProperty(i)) {
                   links[i].addEventListener('click', function (link) {
                       var step = link.getAttribute('data-step');
                       this.raisePostBackEvent('navigateToStep', step);
                   }.bind(this, links[i]));
               }
           }
       }
   }
});