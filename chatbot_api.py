from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import mysql.connector
from google import genai
from google.genai import types

# =========================================================
# FASTAPI APP SETUP
# =========================================================
app = FastAPI()

# CORS ආරක්ෂණ නීති ලිහිල් කිරීම (PHP එකට ලෙහෙසියෙන්ම සම්බන්ධ වීමට)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# =========================================================
# GEMINI API CONFIGURATION
# =========================================================
# 🚨 කරුණාකර ඔයාගේ ක්‍රියාකාරී අලුත්ම Gemini API Key එක මෙතනට දාන්න
GEMINI_API_KEY = "AIzaSyBgP3WIiDscXomEIcGcdegiHQMsECBy8DM" 
client = genai.Client(api_key=GEMINI_API_KEY)

class ChatRequest(BaseModel):
    student_id: int
    message: str

# =========================================================
# DATABASE CONNECTION
# =========================================================
def get_db_connection():
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="attendence"
        )
        return conn
    except mysql.connector.Error as err:
        print("DATABASE ERROR:", err)
        return None

# =========================================================
# 🧠 SMART INTENT CLASSIFIER (ප්‍රශ්න කාණ්ඩය වෙන් කිරීම)
# =========================================================
def predict_intent(user_message: str) -> str:
    """
    ළමයා අහන ප්‍රශ්නය attendance, payments, materials ද නැත්නම් general ද කියා වෙන් කරයි.
    """
    try:
        classification_prompt = f"""
        Classify the following student user message into exactly one of these categories:
        - attendance (If asking about present/absent dates, attendance records, being late)
        - payments (If asking about fees, class cards, amounts paid, pending months)
        - materials (If asking about tutes, papers, video links, pdf, LMS uploads)
        - general (If it's a greeting like 'hi', 'hello', or unrelated to the above)

        User Message: "{user_message}"
        
        Respond with ONLY the category name in lowercase (attendance, payments, materials, or general).
        Do not include any other words or punctuation.
        """
        response = client.models.generate_content(
            model="gemini-2.5-flash",
            contents=classification_prompt,
            config=types.GenerateContentConfig(
                temperature=0.0, # නිවැරදිවම එක වර්ගයක් තෝරාගැනීමට 0.0 දමයි
                max_output_tokens=10
            )
        )
        intent = response.text.strip().lower()
        return intent if intent in ['attendance', 'payments', 'materials', 'general'] else 'general'
    except Exception as e:
        print("Intent Classification Error:", e)
        return "general"

# =========================================================
# 🎯 DYNAMIC STUDENT CONTEXT (අවශ්‍ය දත්ත පමණක් ලබා ගැනීම)
# =========================================================
def get_optimized_student_context(student_id: int, intent: str):
    conn = get_db_connection()
    if not conn:
        return None

    # buffered=True දැමීමෙන් එක දිගට Query දිවීමේදී සිදුවන Crash වීම් වැළකේ
    cursor = conn.cursor(dictionary=True, buffered=True)
    data = {"intent": intent}

    try:
        # 1. ශිෂ්‍යයාගේ මූලික විස්තර
        cursor.execute("SELECT * FROM students WHERE student_id = %s", (student_id,))
        student = cursor.fetchone()
        if not student:
            conn.close()
            return None
        data["student"] = student

        # 2. ශිෂ්‍යයා සහභාගී වන පන්ති
        cursor.execute("""
            SELECT c.subject, t.name AS teacher_name 
            FROM student_classes sc
            JOIN classes c ON sc.class_id = c.id
            LEFT JOIN teachers t ON c.teacher_id = t.id
            WHERE sc.student_id = %s
        """, (student_id,))
        data["classes"] = cursor.fetchall()

        # 3. ප්‍රශ්නයට අදාළ දත්ත කොටස පමණක් ලෝඩ් කිරීම
        if intent == "attendance" or intent == "general":
            cursor.execute("""
                SELECT a.date, a.time, a.status, c.subject FROM attendance a
                JOIN classes c ON a.class_id = c.id
                WHERE a.student_id = %s ORDER BY a.id DESC LIMIT 10
            """, (student_id,))
            data["attendance"] = cursor.fetchall()

        if intent == "payments" or intent == "general":
            cursor.execute("""
                SELECT p.month, p.amount, p.paid_date, c.subject FROM payments p
                JOIN classes c ON p.class_id = c.id
                WHERE p.student_id = %s ORDER BY p.id DESC LIMIT 10
            """, (student_id,))
            data["payments"] = cursor.fetchall()

        if intent == "materials" or intent == "general":
            cursor.execute("""
                SELECT cm.title, cm.material_type, cm.week_no, c.subject FROM class_materials cm
                JOIN student_classes sc ON cm.class_id = sc.class_id
                JOIN classes c ON c.id = sc.class_id
                WHERE sc.student_id = %s ORDER BY cm.id DESC LIMIT 15
            """, (student_id,))
            data["materials"] = cursor.fetchall()

    except mysql.connector.Error as db_err:
        print("SQL execution error:", db_err)
    finally:
        cursor.close()
        conn.close()

    return data

# =========================================================
# CHAT API ENDPOINT
# =========================================================
@app.post("/api/chat")
async def chat(request: ChatRequest):
    # 1. ප්‍රශ්න වර්ගය හඳුනා ගැනීම
    intent = predict_intent(request.message)
    print(f"👉 Detected Intent: {intent}")

    # 2. අවශ්‍ය දත්ත පමණක් ලබා ගැනීම
    context_data = get_optimized_student_context(request.student_id, intent)
    if not context_data:
        raise HTTPException(status_code=404, detail="Student not found")

    # 3. AI එක සඳහා සිස්ටම් ප්‍රොම්ප්ට් එක සෑදීම
    system_prompt = f"""
    You are Sigma Institute AI Student Assistant.
    You must answer in Sinhala language.
    Keep answers short, helpful, friendly, and clear.

    Student Information:
    Name: {context_data['student']['student_name']}
    Grade: {context_data['student']['registered_grade']}
    Student Classes: {context_data['classes']}
    """

    if "attendance" in context_data:
        system_prompt += f"\nAttendance Records:\n{context_data['attendance']}"
    if "payments" in context_data:
        system_prompt += f"\nPayment Records:\n{context_data['payments']}"
    if "materials" in context_data:
        system_prompt += f"\nLMS Materials:\n{context_data['materials']}"

    system_prompt += """
    IMPORTANT RULES:
    - Only answer based on provided database data.
    - If user asks about something and data is missing, say you don't have that info.
    - Never make up fake data.
    """

    # 4. Gemini හරහා පිළිතුර ලබා ගැනීම
    try:
        response = client.models.generate_content(
            model="gemini-2.5-flash",
            contents=request.message,
            config=types.GenerateContentConfig(
                system_instruction=system_prompt,
                temperature=0.4,
                max_output_tokens=300
            )
        )

        reply = response.text
        if not reply:
            reply = "සමාවෙන්න, මට පිළිතුරක් සකස් කර ගැනීමට නොහැකි විය."

        # සිංහලෙන් Category එක පෙන්වීමට සකස් කිරීම
        category_mapping = {
            "attendance": "[පැමිණීම සම්බන්ධ ප්‍රශ්නයක්]",
            "payments": "[ගෙවීම් සම්බන්ධ ප්‍රශ්නයක්]",
            "materials": "[LMS/Tute සම්බන්ධ ප්‍රශ්නයක්]",
            "general": "[පොදු/සාමාන්‍ය ප්‍රශ්නයක්]"
        }
        display_category = category_mapping.get(intent, "[පොදු/සාමාන්‍ය ප්‍රශ්නයක්]")
        
        # අවසාන පිළිතුර ලෙස Category එක උඩින්ම එකතු කර යවයි
        final_reply = f"{display_category}\n\n{reply}"

        return {"reply": final_reply}

    except Exception as e:
        print("CRITICAL GEMINI ERROR:", str(e))
        raise HTTPException(status_code=500, detail=str(e))
    
