/**
 * Add jQuery Validation plugin method for a valid password
 * 
 * Valid passwords contain at least one letter and one number.
 */
$.validator.addMethod('validPassword',
    function(value, element, param) {

        if (value != '') {
            if (value.match(/.*[a-z]+.*/i) == null) {
                return false;
            }
            if (value.match(/.*\d+.*/) == null) {
                return false;
            }
        }

        return true;
    },
    'Must contain at least one letter and one number'
);

if(document.getElementById('myChart')) {

    var dateReq = new XMLHttpRequest(); // Create a new request object
    //dateReq.onload = function() {
    //  let liftData = (this.responseText);
    //  let jsonLiftData = JSON.parse(liftData);
    //  let dates = [];
    //  for (const property in jsonLiftData) {
    //      //console.log(`${property}: ${jsonLiftData[property]}`);
    //      dates.push(jsonLiftData[property]);
    //  }
    //  return dates;
    //};
  
    // Send request for data to "api" route
    dateReq.open("get", "api/dates", true);
    dateReq.send();

    var weightReq = new XMLHttpRequest(); // Create a new request object
    //weightReq.onload = function() {
    //  let weightData = (this.responseText);
    //  let jsonWeightData = JSON.parse(weightData);
    //  let totalWeight = [];
    //  for (const property in jsonWeightData) {
    //      //console.log(`${property}: ${jsonLiftData[property]}`);
    //      totalWeight.push(jsonWeightData);
    //  }
    //  return totalWeight;
    //};

    // Send request for data to "api" route
    weightReq.open("get", "api/dates", true);
    weightReq.send();
    
    const labels =  dateReq.onload = function() {
  
        let liftData = (this.responseText);
    
        let jsonLiftData = JSON.parse(liftData);
    
        let dates = [];
    
        for (const property in jsonLiftData) {
            //console.log(`${property}: ${jsonLiftData[property]}`);
            dates.push(jsonLiftData[property]);
        }
        return dates;
      };
      
        const data = {
          labels: labels,
          datasets: [{
            label: 'Weight Moved',
            backgroundColor: 'rgb(255, 99, 132)',
            borderColor: 'rgb(255, 99, 132)',
            data:     weightReq.onload = function() {
                let weightData = (this.responseText);
                let jsonWeightData = JSON.parse(weightData);
                let totalWeight = [];
                for (const property in jsonWeightData) {
                    //console.log(`${property}: ${jsonLiftData[property]}`);
                    totalWeight.push(jsonWeightData);
                }
          
                return totalWeight;
              },
          }]
        };
      
        const config = {
          type: 'line',
          data: data,
          options: {}
        };
      
        const myChart = new Chart(
          document.getElementById('myChart'),
          config
        );
}


