const allSquares = document.getElementsByClassName("square");

const clickedElements=[];
for(const x of allSquares){
    x.addEventListener("click",function(){

        console.log(clickedElements);


        const idOfElement= x.getAttribute("id");
        const innerHTMLOfElement =document.getElementById(idOfElement).innerHTML;

        if(clickedElements.length>0){
            clickedElements[0].removeAttribute("style");
            clickedElements.length=0;
        }
        
        console.log(x);
        if(
            innerHTMLOfElement.includes("black")||
            innerHTMLOfElement.includes("white")
    ){
        document.getElementById(idOfElement).style.backgroundColor="yellow";
        clickedElements.push(x);
    }
    });
}